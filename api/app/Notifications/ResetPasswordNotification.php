<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Courriel de reinitialisation de mot de passe, en francais.
 *
 * Le lien pointe vers l'application cliente (FRONTEND_URL), pas vers l'API :
 * c'est elle qui affiche le formulaire, puis rappelle l'API avec le jeton.
 * La meme adresse servira de lien profond pour l'application Flutter.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('client.url'), '/')
            . '/reinitialiser-mot-de-passe'
            . '?token=' . $this->token
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        $minutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reinitialisation de ton mot de passe')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Tu as demande a reinitialiser ton mot de passe sur Communaute Prions Ensemble.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line("Ce lien expire dans {$minutes} minutes.")
            ->line('Si tu n\'es pas a l\'origine de cette demande, tu peux ignorer ce message : ton mot de passe reste inchange.')
            ->salutation('Que Dieu te benisse.');
    }
}
