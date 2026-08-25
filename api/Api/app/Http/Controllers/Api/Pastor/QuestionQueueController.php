<?php

namespace App\Http\Controllers\Api\Pastor;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Resources\PastorQuestionResource;
use App\Models\Question;
use App\Services\PastoralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * L'espace pastoral — l'ecran qui n'existait nulle part dans le prototype.
 *
 * Toutes ces routes passent par le middleware EnsurePastor.
 */
class QuestionQueueController extends Controller
{
    public function __construct(private readonly PastoralService $pastoral)
    {
    }

    /** GET /api/pastor/questions — la file, du plus ancien au plus recent. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return PastorQuestionResource::collection(
            $this->pastoral->queue($request->input('topic'))
        );
    }

    /** GET /api/pastor/stats — le tableau de bord. */
    public function stats(): JsonResponse
    {
        return response()->json($this->pastoral->stats());
    }

    /** GET /api/pastor/questions/{question} */
    public function show(Question $question): PastorQuestionResource
    {
        return new PastorQuestionResource(
            $question->load(['user:id,name,avatar_path', 'answer'])
        );
    }

    /** POST /api/pastor/questions/{question}/claim — prendre en charge. */
    public function claim(Request $request, Question $question): PastorQuestionResource
    {
        $question = $this->pastoral->claim($question, $request->user());

        return new PastorQuestionResource($question->load(['user:id,name', 'answer']));
    }

    /**
     * PUT /api/pastor/questions/{question}/answer
     *
     * Enregistre un brouillon. Publie seulement si publish=true est envoye
     * explicitement : rien ne part vers le membre par accident.
     */
    public function answer(AnswerQuestionRequest $request, Question $question): JsonResponse
    {
        $answer = $this->pastoral->draftAnswer(
            $question,
            $request->user(),
            $request->string('body')->toString()
        );

        if ($request->boolean('publish')) {
            $answer = $this->pastoral->publish($answer);
        }

        return response()->json([
            'message' => $answer->isPublished()
                ? 'Reponse publiee : le membre la voit desormais.'
                : 'Brouillon enregistre. Il reste invisible pour le membre.',
            'question' => new PastorQuestionResource(
                $question->refresh()->load(['user:id,name', 'answer'])
            ),
        ]);
    }

    /** POST /api/pastor/questions/{question}/publish — publier un brouillon. */
    public function publish(Question $question): JsonResponse
    {
        $answer = $question->answer;

        if (! $answer) {
            return response()->json([
                'message' => 'Aucun brouillon a publier pour cette question.',
            ], 422);
        }

        $this->pastoral->publish($answer);

        return response()->json([
            'message' => 'Reponse publiee.',
            'question' => new PastorQuestionResource(
                $question->refresh()->load(['user:id,name', 'answer'])
            ),
        ]);
    }
}
