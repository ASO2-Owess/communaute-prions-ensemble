<?php

namespace Tests\Feature;

use App\Support\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
    }

    public function test_lire_un_chapitre_rapporte_des_points(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertCreated()
            ->assertJsonPath('first_read', true)
            ->assertJsonPath('points_awarded', Points::READ)
            ->assertJsonPath('progress.chapters_read', 1)
            ->assertJsonPath('progress.chapters_total', 1189);

        $this->assertSame(Points::READ, $user->refresh()->points_total);
    }

    /**
     * LE test le plus important du projet : sans lui, n'importe qui gagne des
     * points a l'infini en rouvrant Genese 1.
     */
    public function test_relire_le_meme_chapitre_ne_rapporte_rien(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user)
            ->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertOk()                       // 200 et non 201 : rien de cree
            ->assertJsonPath('first_read', false)
            ->assertJsonPath('points_awarded', 0);

        $this->assertSame(Points::READ, $user->refresh()->points_total);
        $this->assertDatabaseCount('readings', 1);
    }

    public function test_un_chapitre_inexistant_est_refuse(): void
    {
        $user = $this->member();

        // La Genese compte 50 chapitres.
        $this->actingAs($user)
            ->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 51])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('chapter');

        $this->assertDatabaseCount('readings', 0);
    }

    public function test_un_livre_inexistant_est_refuse(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/readings', ['book_id' => 67, 'chapter' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('book_id');
    }

    public function test_la_liste_ne_renvoie_que_les_chapitres_du_cycle_en_cours(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 2]);
        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 19, 'chapter' => 23]);

        $this->actingAs($user)->getJson('/api/v1/readings')
            ->assertOk()
            ->assertJsonPath('cycle', 1)
            ->assertJsonPath('count', 3)
            ->assertJsonPath('chapters.1', [1, 2])
            ->assertJsonPath('chapters.19', [23]);
    }

    public function test_mediter_rapporte_plus_que_lire(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->postJson('/api/v1/meditations', ['book_id' => 43, 'chapter' => 3])
            ->assertCreated()
            ->assertJsonPath('points_awarded', Points::MEDITATION);

        $this->assertSame(Points::MEDITATION, $user->refresh()->points_total);
    }

    public function test_un_quiz_peut_etre_rejoue_et_rapporte_a_chaque_fois(): void
    {
        $user = $this->member();

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($user)->postJson('/api/v1/quiz-attempts', [
                'scope' => 'general',
                'score' => 7,
                'total' => 10,
            ])->assertCreated();
        }

        $this->assertSame(3 * Points::QUIZ, $user->refresh()->points_total);
        $this->assertDatabaseCount('quiz_attempts', 3);
    }

    public function test_un_score_superieur_au_nombre_de_questions_est_refuse(): void
    {
        $this->actingAs($this->member())->postJson('/api/v1/quiz-attempts', [
            'scope' => 'general',
            'score' => 12,
            'total' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors('score');
    }

    public function test_le_niveau_progresse_avec_les_points(): void
    {
        $user = $this->member();

        $this->actingAs($user)->getJson('/api/v1/progress')
            ->assertJsonPath('level', 'Disciple')
            ->assertJsonPath('points_to_next_level', 150);

        // 30 meditations = 450 points -> Intendant (seuil 400)
        foreach (range(1, 30) as $chapter) {
            $this->actingAs($user)->postJson('/api/v1/meditations', [
                'book_id' => 19, 'chapter' => $chapter,
            ]);
        }

        $this->actingAs($user)->getJson('/api/v1/progress')
            ->assertJsonPath('points_total', 450)
            ->assertJsonPath('level', 'Intendant');
    }
}
