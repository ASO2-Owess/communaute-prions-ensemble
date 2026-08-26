<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Points;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaderboardController extends Controller
{
    /**
     * GET /api/v1/leaderboard
     *
     * Le vrai classement de la communaute — celui du prototype affichait
     * douze noms codes en dur, identiques pour tout le monde.
     */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        $limit = min($request->integer('limit', 20), 100);

        // Tri sur points_total : c'est exactement pour cette requete que ce
        // cache denormalise existe (et qu'il porte un index).
        $top = User::orderByDesc('points_total')
            ->orderBy('id') // depart des ex aequo : stable et previsible
            ->limit($limit)
            ->get(['id', 'name', 'avatar_path', 'points_total']);

        return response()->json([
            'top' => $top->values()->map(fn (User $u, int $i) => [
                'rank' => $i + 1,
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_path
                    ? Storage::disk(config('filesystems.avatars', 'public'))->url($u->avatar_path)
                    : null,
                'points_total' => $u->points_total,
                'level' => Points::levelFor($u->points_total)['name'],
                'is_me' => $u->id === $me->id,
            ]),
            'me' => [
                'rank' => $this->rankOf($me),
                'points_total' => $me->points_total,
                'level' => Points::levelFor($me->points_total)['name'],
            ],
            'total_members' => User::count(),
        ]);
    }

    /**
     * Rang d'un utilisateur : le nombre de personnes strictement devant lui,
     * plus un. Une seule requete COUNT, quel que soit le nombre de membres —
     * bien plus economique que de charger tout le classement pour y chercher
     * sa place.
     */
    private function rankOf(User $user): int
    {
        return User::where('points_total', '>', $user->points_total)->count() + 1;
    }
}
