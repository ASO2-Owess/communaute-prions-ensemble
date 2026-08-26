<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordQuizAttemptRequest;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /** POST /api/v1/quiz-attempts */
    public function store(RecordQuizAttemptRequest $request): JsonResponse
    {
        $attempt = $this->progress->recordQuizAttempt(
            $request->user(),
            $request->attempt()
        );

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'scope' => $attempt->scope,
                'score' => $attempt->score,
                'total' => $attempt->total,
            ],
            'points_awarded' => \App\Support\Points::QUIZ,
            'progress' => $this->progress->summary($request->user()->refresh()),
        ], 201);
    }
}
