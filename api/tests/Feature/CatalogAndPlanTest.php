<?php

namespace Tests\Feature;

use App\Models\FaqEntry;
use App\Models\QuizQuestion;
use App\Models\ReadingPlan;
use Database\Seeders\BibleFigureSeeder;
use Database\Seeders\DonationMethodSeeder;
use Database\Seeders\EncouragementSeeder;
use Database\Seeders\QuizQuestionSeeder;
use Database\Seeders\ReadingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lot 3 : les contenus servis par le serveur, et les plans de lecture.
 */
class CatalogAndPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
    }

    // ---------------------------------------------------------- catalogue

    public function test_les_encouragements_sont_servis_par_l_api(): void
    {
        $this->seed(EncouragementSeeder::class);

        $this->actingAs($this->member())->getJson('/api/v1/encouragements')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'text', 'reference']]]);
    }

    /**
     * Deux membres qui ouvrent l'application le meme jour doivent voir le meme
     * verset : c'est ce qui en fait un sujet de conversation.
     */
    public function test_le_verset_du_jour_est_le_meme_pour_tous(): void
    {
        $this->seed(EncouragementSeeder::class);

        $a = $this->actingAs($this->member())->getJson('/api/v1/encouragements/today')->json('data.id');
        $b = $this->actingAs($this->member())->getJson('/api/v1/encouragements/today')->json('data.id');

        $this->assertSame($a, $b);
    }

    public function test_les_personnages_sont_groupes_par_categorie(): void
    {
        $this->seed(BibleFigureSeeder::class);

        $this->actingAs($this->member())->getJson('/api/v1/figures')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure(['data' => [['slug', 'label', 'people' => [['name', 'slug']]]]]);
    }

    public function test_les_moyens_de_don_viennent_du_serveur(): void
    {
        $this->seed(DonationMethodSeeder::class);

        $this->actingAs($this->member())->getJson('/api/v1/donation-methods')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ---------------------------------------------------------------- quiz

    /**
     * La bonne reponse ne doit JAMAIS voyager avec l'enonce : il suffirait
     * d'ouvrir la reponse reseau pour tricher.
     */
    public function test_les_questions_ne_contiennent_pas_la_bonne_reponse(): void
    {
        $this->seed(QuizQuestionSeeder::class);

        $reponse = $this->actingAs($this->member())
            ->getJson('/api/v1/quiz/questions?count=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->assertArrayNotHasKey('correct_index', $reponse->json('data.0'));
        $this->assertStringNotContainsString('correct_index', $reponse->getContent());
    }

    public function test_le_serveur_corrige_les_reponses(): void
    {
        $this->seed(QuizQuestionSeeder::class);

        $q = QuizQuestion::first();

        $this->actingAs($this->member())->postJson('/api/v1/quiz/check', [
            'answers' => [['question_id' => $q->id, 'choice' => $q->correct_index]],
        ])->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('results.0.is_correct', true);
    }

    // -------------------------------------------------------------- plans

    public function test_le_plan_annuel_couvre_les_1189_chapitres(): void
    {
        $this->seed(ReadingPlanSeeder::class);

        $plan = ReadingPlan::where('slug', 'bible-en-un-an')->first();

        $this->assertNotNull($plan);
        $this->assertSame(365, $plan->days_count);

        // Somme des chapitres de toutes les entrees = la Bible entiere,
        // ni plus ni moins. C'est le controle qui garantit qu'aucun chapitre
        // n'est oublie ni compte deux fois.
        $total = $plan->days->sum(fn ($d) => $d->chapter_to - $d->chapter_from + 1);
        $this->assertSame(1189, $total);

        // Chaque jour a au moins une lecture.
        $this->assertSame(365, $plan->days->pluck('day')->unique()->count());
    }

    public function test_le_plan_court_reprend_les_douze_moments(): void
    {
        $this->seed(ReadingPlanSeeder::class);

        $plan = ReadingPlan::where('slug', 'douze-moments')->first();

        $this->assertSame(12, $plan->days_count);
        $this->assertSame(12, $plan->days()->count());
    }

    public function test_un_membre_peut_suivre_un_plan_et_cocher_ses_jours(): void
    {
        $this->seed(ReadingPlanSeeder::class);
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/subscribe')
            ->assertCreated()
            ->assertJsonPath('data.subscribed', true)
            ->assertJsonPath('data.next_day', 1);

        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/days/1')
            ->assertCreated()
            ->assertJsonPath('completed_days', 1)
            ->assertJsonPath('data.next_day', 2);

        // Idempotent : recocher le meme jour ne cree pas de doublon.
        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/days/1')
            ->assertOk()
            ->assertJsonPath('recorded', false)
            ->assertJsonPath('completed_days', 1);
    }

    public function test_un_jour_hors_du_plan_est_refuse(): void
    {
        $this->seed(ReadingPlanSeeder::class);
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/subscribe');

        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/days/13')
            ->assertStatus(422);
    }

    public function test_terminer_tous_les_jours_acheve_le_plan(): void
    {
        $this->seed(ReadingPlanSeeder::class);
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/v1/reading-plans/douze-moments/subscribe');

        foreach (range(1, 12) as $jour) {
            $this->actingAs($user)->postJson("/api/v1/reading-plans/douze-moments/days/{$jour}");
        }

        $this->assertDatabaseMissing('plan_subscriptions', [
            'user_id' => $user->id,
            'completed_at' => null,
        ]);
    }

    public function test_cocher_un_jour_sans_suivre_le_plan_est_refuse(): void
    {
        $this->seed(ReadingPlanSeeder::class);

        $this->actingAs($this->member())
            ->postJson('/api/v1/reading-plans/douze-moments/days/1')
            ->assertStatus(422);
    }

    // ----------------------------------------------------------------- FAQ

    public function test_seules_les_entrees_publiees_sont_visibles_des_membres(): void
    {
        $pasteur = $this->pastor();

        FaqEntry::create([
            'question' => 'Comment discerner un appel ?',
            'answer' => str_repeat('a', 30),
            'published' => true,
            'author_id' => $pasteur->id,
        ]);

        FaqEntry::create([
            'question' => 'Brouillon en cours de redaction ?',
            'answer' => str_repeat('b', 30),
            'published' => false,
            'author_id' => $pasteur->id,
        ]);

        $this->actingAs($this->member())->getJson('/api/v1/faq')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_un_membre_ne_peut_pas_ecrire_dans_la_faq(): void
    {
        $this->actingAs($this->member())->postJson('/api/v1/pastor/faq', [
            'question' => 'Une question quelconque ?',
            'answer' => str_repeat('a', 30),
        ])->assertForbidden();
    }

    public function test_le_pasteur_cree_une_entree_de_faq(): void
    {
        $this->actingAs($this->pastor())->postJson('/api/v1/pastor/faq', [
            'question' => 'Comment savoir si Dieu me parle ?',
            'answer' => str_repeat('Une reponse pastorale complete. ', 3),
            'topic' => 'foi',
            'published' => true,
        ])->assertCreated()->assertJsonPath('data.published', true);

        $this->assertDatabaseCount('faq_entries', 1);
    }
}
