<?php

namespace Tests\Feature;

use App\Models\Reading;
use App\Support\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cycles de lecture et relectures (ADR-009).
 *
 * Regle produit : la lecture est libre — relire, decocher, recommencer, dans
 * n'importe quel ordre, comme avec une Bible papier. Seuls les POINTS sont
 * contraints.
 */
class ReadingCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
    }

    // ------------------------------------------------------------- relectures

    public function test_relire_un_chapitre_est_toujours_possible_et_compte(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertCreated()
            ->assertJsonPath('first_read', true)
            ->assertJsonPath('read_count', 1);

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertOk()
            ->assertJsonPath('first_read', false)
            ->assertJsonPath('points_awarded', 0)
            ->assertJsonPath('read_count', 2);

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertJsonPath('read_count', 3);

        // Une seule ligne, un compteur : la relecture ne cree pas de doublon.
        $this->assertDatabaseCount('readings', 1);
        $this->assertSame(Points::READ, $user->refresh()->points_total);
    }

    public function test_le_nombre_de_relectures_est_expose(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 43, 'chapter' => 3]);
        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 43, 'chapter' => 3]);

        $this->actingAs($user)->getJson('/api/v1/readings')
            ->assertOk()
            ->assertJsonPath('counts.43-3', 2);
    }

    // --------------------------------------------------------------- decocher

    public function test_decocher_un_chapitre_le_retire_de_la_liste(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertOk()
            ->assertJsonPath('progress.chapters_read', 0);

        $this->actingAs($user)->getJson('/api/v1/readings')->assertJsonPath('count', 0);
    }

    public function test_decocher_ne_reprend_pas_les_points_deja_gagnes(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->deleteJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->assertSame(Points::READ, $user->refresh()->points_total);
    }

    /**
     * La faille que le systeme doit fermer : decocher puis relire ne doit pas
     * redonner les points, sinon le classement se truque en trois clics.
     */
    public function test_decocher_puis_relire_ne_redonne_pas_de_points(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($user)->deleteJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
            $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        }

        $this->assertSame(Points::READ, $user->refresh()->points_total);
    }

    public function test_relire_recoche_automatiquement(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->deleteJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user)->getJson('/api/v1/readings')->assertJsonPath('count', 1);
    }

    // ---------------------------------------------------------------- cycles

    public function test_on_peut_recommencer_sans_avoir_tout_lu(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading')
            ->assertOk()
            ->assertJsonPath('progress.reading_cycle', 2)
            ->assertJsonPath('progress.chapters_read', 0);
    }

    public function test_un_cycle_vide_ne_peut_pas_etre_reinitialise(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/progress/reset-reading')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'empty');
    }

    /**
     * Sans ce delai : lire un chapitre (+5), reinitialiser, relire (+5)...
     * a l'infini.
     */
    public function test_un_delai_separe_deux_reinitialisations(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading')->assertOk();

        $this->actingAs($user->refresh())->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user->refresh())
            ->postJson('/api/v1/progress/reset-reading')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'cooldown')
            ->assertJsonStructure(['next_allowed_at']);
    }

    public function test_le_delai_ecoule_reautorise_la_reinitialisation(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);
        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading')->assertOk();

        // On recule la derniere reinitialisation au-dela du delai.
        $user->refresh()->forceFill([
            'last_reading_reset_at' => now()->subHours(config('reading.reset_cooldown_hours') + 1),
        ])->save();

        $this->actingAs($user->refresh())->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1]);

        $this->actingAs($user->refresh())
            ->postJson('/api/v1/progress/reset-reading')
            ->assertOk()
            ->assertJsonPath('progress.reading_cycle', 3);
    }

    public function test_reinitialiser_ne_supprime_aucune_lecture(): void
    {
        $user = $this->member();
        $this->readWholeBible($user);

        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading')->assertOk();

        $this->assertDatabaseCount('readings', 1189);
        $this->assertSame(1189, Reading::where('cycle', 1)->count());
        $this->assertSame(0, Reading::where('cycle', 2)->count());
    }

    /**
     * forceFill et non update() : points_total n'est PAS dans $fillable,
     * justement pour qu'aucune requete entrante ne puisse le modifier.
     * Un update() ordinaire l'ignorerait silencieusement — c'est d'ailleurs ce
     * qui faisait echouer ce test avant, et c'etait une bonne nouvelle.
     */
    public function test_le_score_ne_diminue_jamais_apres_une_reinitialisation(): void
    {
        $user = $this->member();
        $this->readWholeBible($user);
        $user->forceFill(['points_total' => 5945])->save();

        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading')->assertOk();

        $this->assertSame(5945, $user->refresh()->points_total);
    }

    public function test_relire_dans_le_nouveau_cycle_rapporte_a_nouveau(): void
    {
        $user = $this->member();
        $this->readWholeBible($user);
        $user->forceFill(['points_total' => 0])->save();

        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading');

        $this->actingAs($user->refresh())
            ->postJson('/api/v1/readings', ['book_id' => 1, 'chapter' => 1])
            ->assertCreated()
            ->assertJsonPath('points_awarded', Points::READ);

        // Genese 1 existe desormais deux fois : une par cycle.
        $this->assertSame(2, Reading::where('user_id', $user->id)
            ->where('book_id', 1)->where('chapter', 1)->count());
    }

    public function test_avoir_tout_lu_est_signale(): void
    {
        $user = $this->member();
        $this->readWholeBible($user);

        $this->actingAs($user)->getJson('/api/v1/progress')
            ->assertJsonPath('chapters_read', 1189)
            ->assertJsonPath('bible_completed', true);
    }

    public function test_le_nombre_de_bibles_lues_est_deduit_du_cycle(): void
    {
        $user = $this->member();
        $this->readWholeBible($user);
        $this->actingAs($user)->postJson('/api/v1/progress/reset-reading');

        $this->actingAs($user->refresh())->getJson('/api/v1/progress')
            ->assertJsonPath('bibles_completed', 1)
            ->assertJsonPath('reading_cycle', 2);
    }
}
