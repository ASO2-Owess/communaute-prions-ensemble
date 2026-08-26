<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /** GET /api/v1/progress */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->progress->summary($request->user()));
    }

    /**
     * POST /api/v1/progress/reset-reading
     *
     * Ouvre un nouveau cycle. Autorise a tout moment (ADR-009), sauf si le
     * cycle en cours est vide ou si le delai entre deux reinitialisations
     * n'est pas ecoule. Les points ne sont jamais touches.
     */
    public function resetReading(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->progress->resetReadingCycle($user);

        if (! $result['ok']) {
            // 422 : la requete est bien formee, mais l'etat actuel ne permet
            // pas l'operation. Ce n'est ni une erreur d'authentification (401)
            // ni un droit manquant (403).
            return response()->json([
                'message' => match ($result['reason']) {
                    'empty' => 'Lis au moins un chapitre avant de recommencer un cycle.',
                    'cooldown' => 'Tu as deja recommence recemment. Reessaie plus tard.',
                    default => 'Impossible de recommencer un cycle pour le moment.',
                },
                'reason' => $result['reason'],
                'next_allowed_at' => $result['next_allowed_at'],
                'progress' => $this->progress->summary($user),
            ], 422);
        }

        return response()->json([
            'message' => 'Nouveau cycle de lecture ouvert. Tes points sont conserves.',
            'progress' => $this->progress->summary($user->refresh()),
        ]);
    }
}
