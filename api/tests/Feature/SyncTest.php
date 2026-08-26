<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\QuizAttempt;
use App\Models\Reading;
use App\Support\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Synchronisation hors ligne (lot 4).
 *
 * Ces tests couvrent des situations qu'on ne rencontre JAMAIS en testant avec
 * du réseau — et qui seront le quotidien du public visé.
 */
class SyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
    }

    public function test_un_lot_de_lectures_est_applique_en_une_requete(): void
    {
        $user = $this->member();

        $items = [];
        for ($chapitre = 1; $chapitre <= 10; $chapitre++) {
            $items[] = ['type' => 'reading', 'book_id' => 1, 'chapter' => $chapitre];
        }

        $this->actingAs($user)->postJson('/api/v1/sync', ['items' => $items])
            ->assertOk()
            ->assertJsonPath('applied', 10)
            ->assertJsonPath('points_awarded', 10 * Points::READ);

        $this->assertSame(10 * Points::READ, $user->refresh()->points_total);
    }

    /**
     * LE test du lot 4 : une coupure au mauvais moment fait renvoyer le lot.
     * Rien ne doit être compté deux fois.
     */
    public function test_renvoyer_le_meme_lot_ne_compte_rien_deux_fois(): void
    {
        $user = $this->member();
        $uuid = (string) Str::uuid();

        $items = [
            ['type' => 'reading', 'book_id' => 1, 'chapter' => 1],
            ['type' => 'meditation', 'book_id' => 43, 'chapter' => 3],
            ['type' => 'quiz_attempt', 'client_uuid' => $uuid, 'scope' => 'general', 'score' => 8, 'total' => 10],
        ];

        $premier = $this->actingAs($user)->postJson('/api/v1/sync', ['items' => $items]);
        $premier->assertOk()->assertJsonPath('applied', 3);

        $points = $user->refresh()->points_total;

        $second = $this->actingAs($user)->postJson('/api/v1/sync', ['items' => $items]);
        $second->assertOk()
            ->assertJsonPath('applied', 0)
            ->assertJsonPath('ignored', 3)
            ->assertJsonPath('points_awarded', 0);

        $this->assertSame($points, $user->refresh()->points_total);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    /**
     * Sans identifiant client, rien ne distingue « deuxième partie de quiz »
     * de « même partie renvoyée après une coupure ».
     */
    public function test_un_quiz_sans_identifiant_client_est_refuse(): void
    {
        $this->actingAs($this->member())->postJson('/api/v1/sync', [
            'items' => [
                ['type' => 'quiz_attempt', 'scope' => 'general', 'score' => 5, 'total' => 10],
            ],
        ])->assertOk()->assertJsonPath('applied', 0)->assertJsonCount(1, 'errors');
    }

    /**
     * Une lecture faite mardi en mode avion doit compter MARDI. Sinon les
     * séries de jours consécutifs sont fausses pour tous ceux qui lisent hors
     * ligne — c'est-à-dire le public visé.
     */
    public function test_l_horodatage_du_client_est_respecte(): void
    {
        $user = $this->member();
        $mardi = now()->subDays(3)->startOfDay()->addHours(9);

        $this->actingAs($user)->postJson('/api/v1/sync', [
            'items' => [[
                'type' => 'reading',
                'book_id' => 1,
                'chapter' => 1,
                'occurred_at' => $mardi->toIso8601String(),
            ]],
        ])->assertOk();

        $lecture = Reading::first();
        $this->assertSame($mardi->toDateString(), $lecture->read_at->toDateString());

        $this->assertDatabaseHas('activity_days', [
            'user_id' => $user->id,
            'day' => $mardi->toDateString(),
        ]);
    }

    /**
     * On fait confiance au client pour la date, mais pas aveuglément : une
     * date future permettrait de fabriquer des séries de jours consécutifs.
     */
    public function test_une_date_future_est_ramenee_a_maintenant(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/sync', [
            'items' => [[
                'type' => 'reading',
                'book_id' => 1,
                'chapter' => 1,
                'occurred_at' => now()->addYear()->toIso8601String(),
            ]],
        ])->assertOk();

        $this->assertSame(now()->toDateString(), Reading::first()->read_at->toDateString());
    }

    /** Un élément fautif ne doit pas faire échouer les autres. */
    public function test_un_element_invalide_n_empeche_pas_les_autres(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/sync', [
            'items' => [
                ['type' => 'reading', 'book_id' => 1, 'chapter' => 1],
                ['type' => 'reading', 'book_id' => 1, 'chapter' => 999], // Genèse n'a que 50 chapitres
                ['type' => 'reading', 'book_id' => 1, 'chapter' => 2],
            ],
        ])->assertOk()
            ->assertJsonPath('applied', 2)
            ->assertJsonCount(1, 'errors')
            ->assertJsonPath('errors.0.index', 1);

        $this->assertDatabaseCount('readings', 2);
    }

    public function test_les_notes_se_synchronisent_par_identifiant_client(): void
    {
        $user = $this->member();
        $uuid = (string) Str::uuid();

        $this->actingAs($user)->postJson('/api/v1/sync', [
            'items' => [['type' => 'note', 'client_uuid' => $uuid, 'content' => 'Premiere version.']],
        ])->assertOk();

        // Modifiee hors ligne, puis renvoyee : c'est une mise a jour, pas une
        // seconde note.
        $this->actingAs($user)->postJson('/api/v1/sync', [
            'items' => [['type' => 'note', 'client_uuid' => $uuid, 'content' => 'Version corrigee.']],
        ])->assertOk();

        $this->assertDatabaseCount('notes', 1);
        $this->assertSame('Version corrigee.', Note::first()->content);
    }

    public function test_la_reception_renvoie_les_reponses_du_pasteur(): void
    {
        $membre = $this->member();
        $pasteur = $this->pastor();

        $question = \App\Models\Question::create([
            'user_id' => $membre->id,
            'body' => str_repeat('a', 30),
            'status' => 'pending',
        ]);

        $this->actingAs($pasteur)->putJson("/api/v1/pastor/questions/{$question->id}/answer", [
            'body' => 'Voici la reponse publiee pour ce membre, relue et validee.',
            'publish' => true,
        ]);

        $this->actingAs($membre)->getJson('/api/v1/sync?since=' . now()->subDay()->toIso8601String())
            ->assertOk()
            ->assertJsonPath('answers.0.question_id', $question->id)
            ->assertJsonStructure(['server_time', 'answers', 'readings', 'notes', 'progress']);
    }

    public function test_un_lot_trop_gros_est_refuse(): void
    {
        $items = array_fill(0, 501, ['type' => 'reading', 'book_id' => 1, 'chapter' => 1]);

        $this->actingAs($this->member())
            ->postJson('/api/v1/sync', ['items' => $items])
            ->assertUnprocessable();
    }
}
