<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordReadingRequest;
use App\Models\Reading;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    /**
     * Le service est injecte par Laravel : le controleur n'a pas a savoir
     * comment le construire. C'est l'injection de dependances.
     */
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * GET /api/v1/readings
     *
     * Les chapitres coches "lu" dans le cycle EN COURS, groupes par livre :
     *   { "1": [1, 2, 3], "19": [23] }
     *
     * `counts` donne le nombre de lectures par chapitre, pour afficher
     * "lu 3 fois" sans alourdir la liste principale.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $rows = Reading::where('user_id', $user->id)
            ->where('cycle', $user->reading_cycle)
            ->get(['book_id', 'chapter', 'read_count', 'is_read']);

        $lus = $rows->where('is_read', true);

        return response()->json([
            'cycle' => $user->reading_cycle,
            'count' => $lus->count(),
            'chapters' => $lus->groupBy('book_id')
                ->map(fn ($r) => $r->pluck('chapter')->sort()->values()),
            'counts' => $rows->where('read_count', '>', 1)
                ->mapWithKeys(fn ($r) => ["{$r->book_id}-{$r->chapter}" => $r->read_count]),
        ]);
    }

    /**
     * POST /api/v1/readings
     *
     * Toujours accepte : relire est libre. Seule la premiere lecture du cycle
     * rapporte des points.
     */
    public function store(RecordReadingRequest $request): JsonResponse
    {
        $result = $this->progress->recordReading(
            $request->user(),
            $request->book(),
            $request->integer('chapter')
        );

        return response()->json([
            ...$result,
            'progress' => $this->progress->summary($request->user()->refresh()),
        ], $result['first_read'] ? 201 : 200);
    }

    /**
     * DELETE /api/v1/readings
     *
     * Retire la coche "lu". Les points deja gagnes restent acquis.
     */
    public function destroy(RecordReadingRequest $request): JsonResponse
    {
        $retire = $this->progress->unmarkReading(
            $request->user(),
            $request->book(),
            $request->integer('chapter')
        );

        if (! $retire) {
            return response()->json([
                'message' => 'Ce chapitre n\'etait pas marque comme lu.',
            ], 404);
        }

        return response()->json([
            'message' => 'Chapitre decoche. Tes points restent acquis.',
            'progress' => $this->progress->summary($request->user()->refresh()),
        ]);
    }
}
