<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordReadingRequest;
use App\Models\MeditationCompletion;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * GET /api/v1/meditations
     *
     * Les meditations achevees, tous cycles confondus : une meditation reste
     * acquise (voir MODELE-DONNEES 3 bis).
     */
    public function index(Request $request): JsonResponse
    {
        $chapters = MeditationCompletion::where('user_id', $request->user()->id)
            ->get(['book_id', 'chapter'])
            ->groupBy('book_id')
            ->map(fn ($rows) => $rows->pluck('chapter')->sort()->values());

        return response()->json([
            'count' => $chapters->flatten()->count(),
            'chapters' => $chapters,
        ]);
    }

    /** POST /api/v1/meditations */
    public function store(RecordReadingRequest $request): JsonResponse
    {
        $result = $this->progress->recordMeditation(
            $request->user(),
            $request->book(),
            $request->integer('chapter')
        );

        return response()->json([
            ...$result,
            'progress' => $this->progress->summary($request->user()->refresh()),
        ], $result['first_read'] ? 201 : 200);
    }
}
