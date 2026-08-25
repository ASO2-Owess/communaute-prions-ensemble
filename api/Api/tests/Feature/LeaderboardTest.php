<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le classement — celui du prototype affichait douze noms codes en dur,
 * identiques pour tout le monde.
 */
class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_classement_trie_par_points_decroissants(): void
    {
        $this->member(['name' => 'Petit', 'points_total' => 50]);
        $this->member(['name' => 'Grand', 'points_total' => 900]);
        $this->member(['name' => 'Moyen', 'points_total' => 400]);

        $this->actingAs($this->member(['points_total' => 0]))
            ->getJson('/api/leaderboard')
            ->assertOk()
            ->assertJsonPath('top.0.name', 'Grand')
            ->assertJsonPath('top.0.rank', 1)
            ->assertJsonPath('top.1.name', 'Moyen')
            ->assertJsonPath('top.2.name', 'Petit');
    }

    public function test_le_niveau_est_calcule_a_partir_des_points(): void
    {
        $this->actingAs($this->member(['points_total' => 1800]))
            ->getJson('/api/leaderboard')
            ->assertJsonPath('me.level', 'Berger');
    }

    public function test_chacun_se_reconnait_dans_le_classement(): void
    {
        $moi = $this->member(['name' => 'Moi', 'points_total' => 300]);
        $this->member(['points_total' => 900]);

        $response = $this->actingAs($moi)->getJson('/api/leaderboard')->assertOk();

        $this->assertSame(2, $response->json('me.rank'));
        $this->assertTrue(collect($response->json('top'))->firstWhere('is_me', true)['name'] === 'Moi');
    }

    public function test_le_rang_compte_ceux_qui_sont_devant(): void
    {
        $moi = $this->member(['points_total' => 100]);

        foreach ([500, 400, 300] as $points) {
            $this->member(['points_total' => $points]);
        }

        $this->actingAs($moi)->getJson('/api/leaderboard')
            ->assertJsonPath('me.rank', 4)
            ->assertJsonPath('total_members', 4);
    }
}
