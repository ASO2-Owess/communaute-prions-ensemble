<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Courriel de verification d'adresse.
 *
 * La verification est VOLONTAIREMENT NON BLOQUANTE : un membre peut utiliser
 * l'application sans avoir cliqué. Exiger la verification avant d'entrer
 * decouragerait une bonne partie des inscriptions, dans un public qui n'ouvre
 * pas forcement sa boite mail sur telephone.
 *
 * Elle reste importante : sans adresse verifiee, "mot de passe oublie" ne sert
 * a rien. L'application rappelle donc regulierement de la confirmer.
 */
class VerifyEmailNotification extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // URL signee : le lien porte une signature que le serveur verifie.
        // Personne ne peut fabriquer un lien de verification pour l'adresse
        // de quelqu'un d'autre.
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Confirme ton adresse e-mail')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Confirme ton adresse pour pouvoir recuperer ton compte en cas d\'oubli de mot de passe.')
            ->action('Confirmer mon adresse', $url)
            ->line('Ce lien expire dans 60 minutes.')
            ->line('Si tu n\'as pas cree de compte, ignore ce message.')
            ->salutation('Que Dieu te benisse.');
    }
}
