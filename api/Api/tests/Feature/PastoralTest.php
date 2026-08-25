<?php

namespace Tests\Feature;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastoralTest extends TestCase
{
    use RefreshDatabase;

    private const TEXTE = 'Comment discerner un appel de Dieu dans une decision difficile ?';

    public function test_un_membre_peut_poser_une_question(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/questions', ['body' => self::TEXTE, 'topic' => 'vocation'])
            ->assertCreated()
            ->assertJsonPath('data.status', Question::STATUS_PENDING)
            // Une date annoncee, jamais une attente sans horizon.
            ->assertJsonStructure(['data' => ['expected_answer_by']]);
    }

    public function test_une_question_trop_courte_est_refusee(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/questions', ['body' => 'aide moi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    /**
     * La regle la plus sensible du projet : ces textes touchent a la vie
     * spirituelle et parfois intime des gens.
     */
    public function test_un_membre_ne_peut_pas_lire_la_question_d_un_autre(): void
    {
        $auteur = $this->member();
        $curieux = $this->member();

        $question = Question::create([
            'user_id' => $auteur->id,
            'body' => self::TEXTE,
            'status' => Question::STATUS_PENDING,
        ]);

        $this->actingAs($curieux)
            ->getJson("/api/questions/{$question->id}")
            ->assertForbidden();
    }

    public function test_la_liste_ne_contient_que_ses_propres_questions(): void
    {
        $auteur = $this->member();
        $autre = $this->member();

        Question::create(['user_id' => $auteur->id, 'body' => self::TEXTE, 'status' => 'pending']);
        Question::create(['user_id' => $autre->id, 'body' => self::TEXTE, 'status' => 'pending']);

        $this->actingAs($auteur)->getJson('/api/questions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_un_membre_ne_peut_pas_saturer_la_file(): void
    {
        $user = $this->member();
        $max = config('pastoral.max_open_questions_per_user');

        foreach (range(1, $max) as $ignored) {
            $this->actingAs($user)
                ->postJson('/api/questions', ['body' => self::TEXTE])
                ->assertCreated();
        }

        $this->actingAs($user)
            ->postJson('/api/questions', ['body' => self::TEXTE])
            ->assertStatus(429);
    }

    public function test_un_membre_ne_peut_pas_entrer_dans_l_espace_pastoral(): void
    {
        $this->actingAs($this->member())
            ->getJson('/api/pastor/questions')
            ->assertForbidden();

        $this->actingAs($this->member())
            ->getJson('/api/pastor/stats')
            ->assertForbidden();
    }

    public function test_le_pasteur_voit_la_file_du_plus_ancien_au_plus_recent(): void
    {
        $ancienne = Question::create([
            'user_id' => $this->member()->id, 'body' => self::TEXTE, 'status' => 'pending',
        ]);
        // update() respecte $fillable, et created_at n'y est pas : la valeur
        // etait silencieusement ignoree. forceFill contourne la protection —
        // legitime dans un test, jamais dans du code applicatif.
        $ancienne->forceFill(['created_at' => now()->subDays(5)])->save();

        $recente = Question::create([
            'user_id' => $this->member()->id, 'body' => self::TEXTE, 'status' => 'pending',
        ]);

        $this->actingAs($this->pastor())->getJson('/api/pastor/questions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ancienne->id)
            ->assertJsonPath('data.1.id', $recente->id)
            ->assertJsonPath('data.0.waiting_days', 5);
    }

    /**
     * Le point le plus important de la fonctionnalite : un brouillon n'existe
     * pas du point de vue du membre.
     */
    public function test_un_brouillon_reste_invisible_pour_le_membre(): void
    {
        $membre = $this->member();
        $pasteur = $this->pastor();

        $question = Question::create([
            'user_id' => $membre->id, 'body' => self::TEXTE, 'status' => 'pending',
        ]);

        $this->actingAs($pasteur)->putJson("/api/pastor/questions/{$question->id}/answer", [
            'body' => 'Voici un debut de reponse que je souhaite relire demain.',
        ])->assertOk();

        $this->assertDatabaseHas('answers', ['question_id' => $question->id, 'published_at' => null]);

        $this->actingAs($membre)->getJson("/api/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('data.answer', null);
    }

    public function test_publier_rend_la_reponse_visible(): void
    {
        $membre = $this->member();
        $pasteur = $this->pastor();

        $question = Question::create([
            'user_id' => $membre->id, 'body' => self::TEXTE, 'status' => 'pending',
        ]);

        $this->actingAs($pasteur)->putJson("/api/pastor/questions/{$question->id}/answer", [
            'body' => 'Voici la reponse complete, relue et prete a etre publiee.',
            'publish' => true,
        ])->assertOk();

        $this->actingAs($membre)->getJson("/api/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('data.status', Question::STATUS_ANSWERED)
            ->assertJsonPath('data.answer.author', $pasteur->name)
            ->assertJsonPath('data.expected_answer_by', null);
    }

    public function test_prendre_en_charge_change_le_statut(): void
    {
        $pasteur = $this->pastor();

        $question = Question::create([
            'user_id' => $this->member()->id, 'body' => self::TEXTE, 'status' => 'pending',
        ]);

        $this->actingAs($pasteur)
            ->postJson("/api/pastor/questions/{$question->id}/claim")
            ->assertOk()
            ->assertJsonPath('data.status', Question::STATUS_ASSIGNED)
            ->assertJsonPath('data.assigned_to', $pasteur->id);
    }
}
