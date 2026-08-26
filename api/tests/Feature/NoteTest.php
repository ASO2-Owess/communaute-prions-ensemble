<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBooks();
    }

    public function test_une_note_peut_etre_rattachee_a_un_verset(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/notes', [
                'content' => 'A relire quand le decouragement revient.',
                'book_id' => 43,
                'chapter' => 3,
                'verse' => 16,
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'Jean 3.16');
    }

    public function test_une_note_libre_n_a_besoin_d_aucune_reference(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/notes', ['content' => 'Penser a prier pour Aminata.'])
            ->assertCreated()
            ->assertJsonPath('data.reference', null);
    }

    public function test_un_chapitre_sans_livre_est_refuse(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/notes', ['content' => 'Une note.', 'chapter' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('book_id');
    }

    public function test_un_chapitre_inexistant_est_refuse(): void
    {
        $this->actingAs($this->member())
            ->postJson('/api/v1/notes', [
                'content' => 'Une note.', 'book_id' => 65, 'chapter' => 2, // Jude n'a qu'un chapitre
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('chapter');
    }

    /**
     * Contrairement aux questions, l'equipe pastorale n'a AUCUN acces aux
     * notes : ce sont des reflexions privees.
     */
    public function test_personne_ne_peut_modifier_la_note_d_un_autre(): void
    {
        $note = Note::create([
            'user_id' => $this->member()->id,
            'content' => 'Ma reflexion personnelle.',
        ]);

        $this->actingAs($this->member())
            ->putJson("/api/v1/notes/{$note->id}", ['content' => 'Texte modifie.'])
            ->assertForbidden();

        $this->actingAs($this->pastor())
            ->deleteJson("/api/v1/notes/{$note->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('notes', ['id' => $note->id, 'content' => 'Ma reflexion personnelle.']);
    }

    public function test_la_liste_ne_contient_que_ses_propres_notes(): void
    {
        $moi = $this->member();

        Note::create(['user_id' => $moi->id, 'content' => 'La mienne.']);
        Note::create(['user_id' => $this->member()->id, 'content' => 'Celle d\'un autre.']);

        $this->actingAs($moi)->getJson('/api/v1/notes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'La mienne.');
    }

    public function test_les_notes_se_filtrent_par_passage(): void
    {
        $moi = $this->member();

        Note::create(['user_id' => $moi->id, 'content' => 'Sur Jean 3.', 'book_id' => 43, 'chapter' => 3]);
        Note::create(['user_id' => $moi->id, 'content' => 'Sur Psaume 23.', 'book_id' => 19, 'chapter' => 23]);

        $this->actingAs($moi)->getJson('/api/v1/notes?book_id=43')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Sur Jean 3.');
    }
}
