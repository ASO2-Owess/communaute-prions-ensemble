<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visiteur_peut_creer_un_compte(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Aminata Kone',
            'email' => 'aminata@exemple.ci',
            'password' => 'MotDePasse123',
            'password_confirmation' => 'MotDePasse123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'aminata@exemple.ci',
            'role' => User::ROLE_MEMBER,
        ]);
    }

    public function test_le_mot_de_passe_n_est_jamais_stocke_en_clair(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Aminata',
            'email' => 'aminata@exemple.ci',
            'password' => 'MotDePasse123',
            'password_confirmation' => 'MotDePasse123',
        ]);

        $this->assertNotSame('MotDePasse123', User::first()->password);
    }

    public function test_un_email_deja_utilise_est_refuse(): void
    {
        User::factory()->create(['email' => 'aminata@exemple.ci']);

        $this->postJson('/api/v1/register', [
            'name' => 'Une autre',
            'email' => 'aminata@exemple.ci',
            'password' => 'MotDePasse123',
            'password_confirmation' => 'MotDePasse123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_un_mauvais_mot_de_passe_ne_revele_pas_si_le_compte_existe(): void
    {
        User::factory()->create(['email' => 'aminata@exemple.ci']);

        $existant = $this->postJson('/api/v1/login', [
            'email' => 'aminata@exemple.ci',
            'password' => 'mauvais',
        ]);

        $inconnu = $this->postJson('/api/v1/login', [
            'email' => 'personne@exemple.ci',
            'password' => 'mauvais',
        ]);

        // Meme code et meme message : impossible de deviner quelles adresses
        // ont un compte dans la communaute.
        $existant->assertUnprocessable();
        $inconnu->assertUnprocessable();
        $this->assertSame(
            $existant->json('errors.email'),
            $inconnu->json('errors.email')
        );
    }

    /**
     * Se deconnecter ne doit revoquer QUE l'appareil courant. Sans ce test,
     * rien n'empechait /api/v1/logout de supprimer tous les jetons — ou de tomber
     * en erreur, faute de jeton reel a supprimer.
     */
    public function test_se_deconnecter_ne_revoque_que_l_appareil_courant(): void
    {
        $user = $this->member();

        $telephone = $user->createToken('telephone')->plainTextToken;
        $user->createToken('tablette');

        $this->withToken($telephone)->postJson('/api/v1/logout')->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('tablette', $user->tokens()->first()->name);
    }

    /**
     * PIEGE DE TEST, paye une fois : sans `forgetGuards()`, ce test passait au
     * vert alors que la seconde requete etait acceptee A TORT.
     *
     * En production, chaque requete HTTP part d'une application neuve : le
     * garde d'authentification doit relire le jeton dans la base a chaque fois.
     * Dans un test, l'application reste en memoire entre deux appels, et le
     * garde garde en cache l'utilisateur qu'il a resolu la premiere fois. La
     * seconde requete etait donc autorisee par le SOUVENIR de la premiere, pas
     * par le jeton — qui n'existait plus.
     *
     * `forgetGuards()` remet le garde a zero et reproduit la seule chose qu'on
     * veut verifier : un jeton supprime en base ne donne plus acces a rien.
     */
    public function test_le_jeton_revoque_ne_donne_plus_acces(): void
    {
        $user = $this->member();
        $telephone = $user->createToken('telephone')->plainTextToken;

        $this->withToken($telephone)->postJson('/api/v1/logout')->assertOk();

        // La ligne a bien disparu de la base : c'est verifiable sans le garde.
        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withToken($telephone)->getJson('/api/v1/progress')->assertUnauthorized();
    }

    public function test_les_routes_protegees_refusent_un_visiteur(): void
    {
        $this->getJson('/api/v1/progress')->assertUnauthorized();
        $this->getJson('/api/v1/leaderboard')->assertUnauthorized();
        $this->getJson('/api/v1/questions')->assertUnauthorized();
    }

    public function test_un_membre_ne_peut_pas_s_attribuer_des_points(): void
    {
        $user = $this->member();

        // Tentative d'assignation de masse : points_total n'est pas dans
        // $fillable, la valeur doit etre ignoree.
        $this->actingAs($user)->putJson('/api/v1/profile', [
            'name' => 'Nouveau nom',
            'points_total' => 999999,
            'role' => User::ROLE_ADMIN,
        ])->assertOk();

        $user->refresh();

        $this->assertSame(0, $user->points_total);
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertSame('Nouveau nom', $user->name);
    }
}
