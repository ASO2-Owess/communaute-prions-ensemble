<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le contrat d'erreur de l'API.
 *
 * Ces tests existent a cause d'une panne precise : un gestionnaire
 * d'exceptions « attrape-tout » avait remplace celui de Laravel sans
 * reimplementer le cas le plus frequent de tous — la validation. Douze routes
 * repondaient 500 la ou elles devaient repondre 422, et l'application mobile
 * n'avait plus aucun moyen de dire a l'utilisateur QUEL champ etait fautif.
 *
 * Le format des erreurs est une partie de l'API au meme titre que les
 * donnees. Il merite donc d'etre teste comme le reste.
 */
class ErrorFormatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 422 + le detail par champ. C'est ce detail qui permet d'afficher le
     * message sous le bon champ du formulaire.
     */
    public function test_une_erreur_de_validation_sort_en_422_avec_le_detail_des_champs(): void
    {
        $this->postJson('/api/v1/register', ['name' => 'Sans adresse'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
    }

    public function test_un_visiteur_recoit_401_en_json(): void
    {
        $this->getJson('/api/v1/progress')
            ->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_une_route_inconnue_recoit_404_en_json(): void
    {
        $this->getJson('/api/v1/route-qui-n-existe-pas')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    /**
     * LE cas qu'on ne voit jamais en testant avec postJson() : un client qui
     * oublie l'en-tete `Accept: application/json`. Par defaut, Laravel lui
     * renvoie une page HTML — l'application mobile essaie de lire du JSON,
     * echoue, et affiche un ecran blanc sans message.
     *
     * Sur /api/*, la reponse doit rester du JSON quoi qu'il arrive.
     */
    public function test_l_api_repond_en_json_meme_sans_en_tete_accept(): void
    {
        $reponse = $this->post('/api/v1/register', ['name' => 'Sans adresse']);

        $reponse->assertStatus(422);
        $this->assertStringContainsString('application/json', $reponse->headers->get('Content-Type'));
        $this->assertIsArray($reponse->json('errors'));
    }
}
