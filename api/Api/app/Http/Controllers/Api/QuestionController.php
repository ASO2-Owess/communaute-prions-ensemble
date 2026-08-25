<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AskQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\PastoralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cote membre : poser une question et suivre les siennes.
 */
class QuestionController extends Controller
{
    public function __construct(private readonly PastoralService $pastoral)
    {
    }

    /** GET /api/questions — uniquement les siennes. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $questions = Question::where('user_id', $request->user()->id)
            ->with('answer.author:id,name')
            ->latest()
            ->paginate(20);

        return QuestionResource::collection($questions);
    }

    /** GET /api/questions/{question} */
    public function show(Request $request, Question $question): QuestionResource
    {
        // La Policy leve une 403 si ce n'est pas sa question.
        $this->authorize('view', $question);

        return new QuestionResource($question->load('answer.author:id,name'));
    }

    /** POST /api/questions */
    public function store(AskQuestionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->pastoral->canAsk($user)) {
            $max = config('pastoral.max_open_questions_per_user');

            // 429 : trop de demandes. Le message explique la raison plutot que
            // de laisser croire a une panne.
            return response()->json([
                'message' => "Tu as deja {$max} questions en attente de reponse. "
                    . 'Attends une reponse avant d\'en poser une nouvelle.',
                'open_questions' => $this->pastoral->openQuestionCount($user),
            ], 429);
        }

        $question = $this->pastoral->ask(
            $user,
            $request->string('body')->toString(),
            $request->input('topic')
        );

        return (new QuestionResource($question->load('answer')))
            ->response()
            ->setStatusCode(201);
    }
}
