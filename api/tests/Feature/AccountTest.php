<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lot 2 : ce qui manquait a de vrais utilisateurs.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    // --------------------------------------------------- mot de passe oublie

    public function test_une_demande_de_reinitialisation_envoie_un_courriel(): void
    {
        Notification::fake();

        $user = $this->member(['email' => 'aminata@exemple.ci']);

        $this->postJson('/api/v1/password/forgot', ['email' => 'aminata@exemple.ci'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * Repondre "aucun compte avec cette adresse" permettrait de decouvrir qui
     * est membre de la communaute en essayant des adresses une par une.
     */
    public function test_une_adresse_inconnue_recoit_la_meme_reponse(): void
    {
        Notification::fake();

        $connue = $this->postJson('/api/v1/password/forgot', ['email' => 'personne@exemple.ci']);
        $this->member(['email' => 'existe@exemple.ci']);
        $inconnue = $this->postJson('/api/v1/password/forgot', ['email' => 'existe@exemple.ci']);

        $connue->assertOk();
        $inconnue->assertOk();
        $this->assertSame($connue->json('message'), $inconnue->json('message'));
    }

    public function test_un_jeton_invalide_est_refuse(): void
    {
        $this->member(['email' => 'aminata@exemple.ci']);

        $this->postJson('/api/v1/password/reset', [
            'token' => 'jeton-invente',
            'email' => 'aminata@exemple.ci',
            'password' => 'NouveauMotDePasse123',
            'password_confirmation' => 'NouveauMotDePasse123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    // ------------------------------------------------- changement volontaire

    public function test_changer_de_mot_de_passe_exige_l_ancien(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/password/change', [
            'current_password' => 'mauvais',
            'password' => 'NouveauMotDePasse123',
            'password_confirmation' => 'NouveauMotDePasse123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_le_changement_de_mot_de_passe_fonctionne(): void
    {
        // password est hache par le cast du modele.
        $user = User::factory()->create(['password' => 'AncienMotDePasse123'])->fresh();

        $this->actingAs($user)->postJson('/api/v1/password/change', [
            'current_password' => 'AncienMotDePasse123',
            'password' => 'NouveauMotDePasse123',
            'password_confirmation' => 'NouveauMotDePasse123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NouveauMotDePasse123', $user->refresh()->password));
    }

    // --------------------------------------------------------- verification

    public function test_un_membre_peut_demander_la_confirmation_de_son_adresse(): void
    {
        Notification::fake();

        $user = $this->member(['email_verified_at' => null]);

        $this->actingAs($user)
            ->postJson('/api/v1/email/verification-notification')
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    /**
     * La verification ne doit JAMAIS bloquer l'usage de l'application : exiger
     * un clic dans une boite mail avant d'entrer decouragerait une bonne part
     * des inscriptions.
     */
    public function test_une_adresse_non_confirmee_ne_bloque_rien(): void
    {
        $user = $this->member(['email_verified_at' => null]);

        $this->actingAs($user)->getJson('/api/v1/progress')->assertOk();
        $this->actingAs($user)->getJson('/api/v1/leaderboard')->assertOk();
    }

    // -------------------------------------------------------- appareils

    /**
     * Ce test s'authentifie avec un VRAI jeton (en-tete Bearer) au lieu de
     * `actingAs()`.
     *
     * Ce n'est pas un detail de style. `actingAs()` court-circuite Sanctum :
     * il declare l'utilisateur connecte sans creer de jeton, et
     * `currentAccessToken()` rend alors un TransientToken sans identifiant.
     * Impossible, dans ces conditions, de verifier la seule chose qui compte
     * ici : que l'appareil d'ou part la demande n'est PAS deconnecte.
     *
     * Un test qui n'emprunte pas le chemin reel ne prouve rien sur le chemin
     * reel.
     */
    public function test_on_peut_deconnecter_ses_autres_appareils(): void
    {
        $user = $this->member();

        $courant = $user->createToken('telephone-actuel')->plainTextToken;
        $user->createToken('ancien-telephone');
        $user->createToken('tablette');

        $this->withToken($courant)->deleteJson('/api/v1/account/devices')
            ->assertOk()
            ->assertJsonPath('message', '2 appareil(s) deconnecte(s).');

        // Seul le jeton courant survit.
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('telephone-actuel', $user->tokens()->first()->name);
    }

    /**
     * Le cas inverse : pas de jeton d'API courant a epargner. Tous les jetons
     * sont alors « les autres » — et surtout, la route ne doit pas tomber en
     * erreur, ce qu'elle faisait en lisant ->id sur un TransientToken.
     */
    public function test_deconnecter_ses_appareils_sans_jeton_courant_ne_plante_pas(): void
    {
        $user = $this->member();
        $user->createToken('ancien-telephone');

        $this->actingAs($user)->deleteJson('/api/v1/account/devices')->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_la_liste_des_appareils_signale_celui_en_cours(): void
    {
        $user = $this->member();

        $courant = $user->createToken('telephone-actuel')->plainTextToken;
        $user->createToken('tablette');

        $reponse = $this->withToken($courant)->getJson('/api/v1/account/devices')->assertOk();

        $actuels = collect($reponse->json('devices'))->where('is_current', true);

        $this->assertCount(1, $actuels);
        $this->assertSame('telephone-actuel', $actuels->first()['name']);
    }

    // ---------------------------------------------------------- export

    public function test_un_membre_peut_exporter_ses_donnees(): void
    {
        $this->seedBooks();
        $user = $this->member(['name' => 'Aminata']);

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 43, 'chapter' => 3]);
        Note::create(['user_id' => $user->id, 'content' => 'Ma reflexion.']);

        $this->actingAs($user)->getJson('/api/v1/account/export')
            ->assertOk()
            ->assertJsonPath('profil.nom', 'Aminata')
            ->assertJsonPath('lectures.0.reference', 'Jean 3')
            ->assertJsonPath('notes.0.contenu', 'Ma reflexion.');
    }

    // --------------------------------------------------------- suppression

    public function test_la_suppression_exige_le_mot_de_passe_et_une_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'MonMotDePasse123'])->fresh();

        $this->actingAs($user)->deleteJson('/api/v1/account', [
            'password' => 'MonMotDePasse123',
        ])->assertUnprocessable()->assertJsonValidationErrors('confirmation');

        $this->actingAs($user)->deleteJson('/api/v1/account', [
            'password' => 'mauvais',
            'confirmation' => 'SUPPRIMER MON COMPTE',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * Decision produit : quand un membre part, TOUT part. C'est ce qu'on peut
     * promettre sans reserve a quelqu'un d'inquiet de ce qu'il a confie.
     */
    public function test_supprimer_son_compte_efface_toutes_ses_donnees(): void
    {
        $this->seedBooks();
        $user = User::factory()->create(['password' => 'MonMotDePasse123'])->fresh();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        Note::create(['user_id' => $user->id, 'content' => 'Privee.']);
        Question::create(['user_id' => $user->id, 'body' => str_repeat('a', 30), 'status' => 'pending']);

        $this->actingAs($user)->deleteJson('/api/v1/account', [
            'password' => 'MonMotDePasse123',
            'confirmation' => 'SUPPRIMER MON COMPTE',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // Les cles etrangeres en cascade garantissent l'effacement : c'est la
        // BASE qui le fait, pas une liste d'oublis possibles dans le code.
        $this->assertDatabaseMissing('readings', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('notes', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('questions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('activity_days', ['user_id' => $user->id]);
    }

    /**
     * Les reponses d'un pasteur sont lues par d'autres membres : les effacer
     * detruirait le travail pastoral destine a autrui.
     */
    public function test_un_pasteur_ne_peut_pas_supprimer_son_compte(): void
    {
        $pasteur = User::factory()->create([
            'password' => 'MonMotDePasse123',
            'role' => User::ROLE_PASTOR,
        ])->fresh();

        $this->actingAs($pasteur)->deleteJson('/api/v1/account', [
            'password' => 'MonMotDePasse123',
            'confirmation' => 'SUPPRIMER MON COMPTE',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $pasteur->id]);
    }
}
