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
        $response = $this->postJson('/api/register', [
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
        $this->postJson('/api/register', [
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

        $this->postJson('/api/register', [
            'name' => 'Une autre',
            'email' => 'aminata@exemple.ci',
            'password' => 'MotDePasse123',
            'password_confirmation' => 'MotDePasse123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_un_mauvais_mot_de_passe_ne_revele_pas_si_le_compte_existe(): void
    {
        User::factory()->create(['email' => 'aminata@exemple.ci']);

        $existant = $this->postJson('/api/login', [
            'email' => 'aminata@exemple.ci',
            'password' => 'mauvais',
        ]);

        $inconnu = $this->postJson('/api/login', [
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

    public function test_les_routes_protegees_refusent_un_visiteur(): void
    {
        $this->getJson('/api/progress')->assertUnauthorized();
        $this->getJson('/api/leaderboard')->assertUnauthorized();
        $this->getJson('/api/questions')->assertUnauthorized();
    }

    public function test_un_membre_ne_peut_pas_s_attribuer_des_points(): void
    {
        $user = $this->member();

        // Tentative d'assignation de masse : points_total n'est pas dans
        // $fillable, la valeur doit etre ignoree.
        $this->actingAs($user)->putJson('/api/profile', [
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
