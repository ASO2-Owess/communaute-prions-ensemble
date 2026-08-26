<?php

namespace Tests\Feature;

use App\Models\GeneratedContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Le relais IA (ADR-002).
 *
 * Aucun appel reel n'est fait : Http::fake() intercepte tout. Un test qui
 * appellerait vraiment l'API serait lent, couteux, et donnerait un resultat
 * different a chaque execution — donc inutilisable comme filet de securite.
 */
class AiRelayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
        $this->seedVerses(43, 3); // Jean 3
    }

    private function fakeAi(array $payload = ['rhema' => 'Une phrase a mediter.']): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($payload)]],
            ]),
        ]);
    }

    public function test_un_contenu_inedit_part_en_relecture_et_n_est_pas_servi(): void
    {
        $this->fakeAi();

        $this->actingAs($this->member())
            ->getJson('/api/v1/contents/meditation/43/3')
            ->assertStatus(202) // accepte, traitement en cours
            ->assertJsonPath('status', 'pending_review')
            ->assertJsonPath('content', null);

        $this->assertDatabaseHas('generated_contents', [
            'kind' => GeneratedContent::KIND_MEDITATION,
            'reference' => '43-3',
            'status' => GeneratedContent::STATUS_PENDING,
        ]);
    }

    public function test_un_contenu_approuve_est_servi_sans_rappeler_l_ia(): void
    {
        Http::fake(); // aucun appel ne doit partir

        GeneratedContent::create([
            'kind' => GeneratedContent::KIND_MEDITATION,
            'reference' => '43-3',
            'payload' => ['rhema' => 'Dieu a tant aime le monde.'],
            'status' => GeneratedContent::STATUS_APPROVED,
        ]);

        $this->actingAs($this->member())
            ->getJson('/api/v1/contents/meditation/43/3')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('content.rhema', 'Dieu a tant aime le monde.');

        Http::assertNothingSent();
    }

    /**
     * Le coeur de la mutualisation : avec 2 000 membres, le cout est divise
     * par 2 000 parce que 1 999 d'entre eux ne declenchent aucun appel.
     */
    public function test_deux_membres_ne_declenchent_qu_une_seule_generation(): void
    {
        $this->fakeAi();

        $this->actingAs($this->member())->getJson('/api/v1/contents/meditation/43/3');
        $this->actingAs($this->member())->getJson('/api/v1/contents/meditation/43/3');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('generated_contents', 1);
    }

    public function test_le_quota_journalier_bloque_les_generations_en_serie(): void
    {
        $this->fakeAi();
        config(['ai.daily_quota_per_user' => 2]);

        $user = $this->member();

        // Jean 3 est deja insere par setUp : le reinserer violerait la cle
        // primaire (book_id, chapter, number).
        $this->seedVerses(1, 1);

        foreach ([[43, 3], [1, 1]] as [$book, $chapter]) {
            $this->actingAs($user)->getJson("/api/v1/contents/meditation/{$book}/{$chapter}")
                ->assertStatus(202);
        }

        $this->seedVerses(19, 23);
        $this->actingAs($user)->getJson('/api/v1/contents/meditation/19/23')
            ->assertStatus(429)
            ->assertJsonPath('status', 'quota_exceeded');
    }

    public function test_le_pasteur_voit_le_passage_a_cote_du_contenu_a_relire(): void
    {
        $this->fakeAi();
        $this->actingAs($this->member())->getJson('/api/v1/contents/meditation/43/3');

        $content = GeneratedContent::first();

        $this->actingAs($this->pastor())
            ->getJson("/api/v1/pastor/contents/{$content->id}")
            ->assertOk()
            ->assertJsonPath('passage.book', 'Jean')
            ->assertJsonPath('passage.chapter', 3)
            ->assertJsonCount(5, 'passage.verses');
    }

    public function test_approuver_rend_le_contenu_disponible_pour_tous(): void
    {
        $this->fakeAi();
        $this->actingAs($this->member())->getJson('/api/v1/contents/meditation/43/3');

        $content = GeneratedContent::first();

        $this->actingAs($this->pastor())
            ->postJson("/api/v1/pastor/contents/{$content->id}/approve")
            ->assertOk();

        $this->actingAs($this->member())
            ->getJson('/api/v1/contents/meditation/43/3')
            ->assertOk()
            ->assertJsonPath('status', 'ready');
    }

    public function test_un_membre_ne_peut_pas_approuver_un_contenu(): void
    {
        // Pas d'identifiant code en dur : RefreshDatabase utilise une
        // transaction, et MySQL ne remet pas l'auto-increment a zero entre
        // deux tests. L'id "1" n'existe donc pas forcement.
        $content = GeneratedContent::create([
            'kind' => GeneratedContent::KIND_MEDITATION,
            'reference' => '43-3',
            'payload' => [],
            'status' => GeneratedContent::STATUS_PENDING,
        ]);

        $this->actingAs($this->member())
            ->postJson("/api/v1/pastor/contents/{$content->id}/approve")
            ->assertForbidden();
    }

    public function test_un_chapitre_sans_texte_ne_declenche_aucun_appel(): void
    {
        Http::fake();

        // Jean 4 n'a pas ete insere : le serveur doit le dire franchement
        // plutot que d'envoyer un prompt vide a l'IA.
        $this->actingAs($this->member())
            ->getJson('/api/v1/contents/meditation/43/4')
            ->assertStatus(503);

        Http::assertNothingSent();
    }
}
