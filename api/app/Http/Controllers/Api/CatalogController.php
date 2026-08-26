<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationMethod;
use App\Models\Encouragement;
use App\Models\FaqEntry;
use App\Models\FigureCategory;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contenus éditoriaux servis par le serveur (lot 3).
 *
 * Tout ce qui est ici vivait auparavant en dur dans l'application. Corriger
 * une faute, ajouter un personnage ou changer un numéro de don obligeait à
 * republier — et sur les magasins, une republication prend des jours.
 */
class CatalogController extends Controller
{
    /** GET /api/v1/encouragements */
    public function encouragements(): JsonResponse
    {
        return response()->json([
            'data' => Encouragement::published()
                ->get(['id', 'text', 'reference', 'theme']),
        ]);
    }

    /**
     * GET /api/v1/encouragements/today
     *
     * Le verset du jour. Le choix dépend de la DATE, pas du hasard : deux
     * membres qui ouvrent l'application le même jour doivent voir le même
     * verset — c'est ce qui en fait un sujet de conversation dans la
     * communauté.
     */
    public function encouragementOfTheDay(): JsonResponse
    {
        $liste = Encouragement::published()->orderBy('id')->get(['id', 'text', 'reference']);

        if ($liste->isEmpty()) {
            return response()->json(['message' => 'Aucun encouragement publié.'], 404);
        }

        $index = (int) now()->format('z') % $liste->count();

        return response()->json(['data' => $liste[$index]]);
    }

    /** GET /api/v1/figures — les catégories et leurs personnages. */
    public function figures(): JsonResponse
    {
        return response()->json([
            'data' => FigureCategory::with('figures:id,category_id,name,slug,position')
                ->orderBy('position')
                ->get(['id', 'slug', 'label', 'position'])
                ->map(fn ($c) => [
                    'slug' => $c->slug,
                    'label' => $c->label,
                    'people' => $c->figures->map(fn ($f) => [
                        'name' => $f->name,
                        'slug' => $f->slug,
                    ]),
                ]),
        ]);
    }

    /**
     * GET /api/v1/quiz/questions?count=10
     *
     * Questions tirées au sort, **sans la bonne réponse** : `correct_index`
     * est masqué par le modèle. L'envoyer avec l'énoncé permettrait de tricher
     * en lisant simplement la réponse réseau. Le client envoie ses réponses,
     * le serveur tranche.
     */
    public function quizQuestions(Request $request): JsonResponse
    {
        $count = min(max($request->integer('count', 10), 1), 30);

        return response()->json([
            'data' => QuizQuestion::published()
                ->inRandomOrder()
                ->limit($count)
                ->get(['id', 'question', 'options']),
        ]);
    }

    /**
     * POST /api/v1/quiz/check
     *
     * Corrige les réponses. C'est le serveur qui détient la vérité.
     */
    public function checkQuiz(Request $request): JsonResponse
    {
        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1', 'max:30'],
            'answers.*.question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.choice' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $questions = QuizQuestion::whereIn('id', collect($data['answers'])->pluck('question_id'))
            ->get(['id', 'correct_index'])
            ->keyBy('id');

        $resultats = collect($data['answers'])->map(function (array $a) use ($questions) {
            $attendu = $questions[$a['question_id']]->correct_index ?? null;

            return [
                'question_id' => $a['question_id'],
                'choice' => $a['choice'],
                'correct_index' => $attendu,
                'is_correct' => $attendu !== null && $attendu === $a['choice'],
            ];
        });

        return response()->json([
            'score' => $resultats->where('is_correct', true)->count(),
            'total' => $resultats->count(),
            'results' => $resultats,
        ]);
    }

    /** GET /api/v1/donation-methods */
    public function donationMethods(): JsonResponse
    {
        return response()->json([
            'data' => DonationMethod::active()
                ->orderBy('position')
                ->get(['provider', 'label', 'phone', 'note']),
        ]);
    }

    /**
     * GET /api/v1/faq
     *
     * La FAQ pastorale (ADR-007) : la parade au risque n°1 du projet. Les
     * questions se répètent ; y répondre une fois doit servir à tous.
     */
    public function faq(Request $request): JsonResponse
    {
        return response()->json([
            'data' => FaqEntry::published()
                ->when($request->input('topic'), fn ($q, $t) => $q->where('topic', $t))
                ->get(['id', 'question', 'answer', 'topic']),
        ]);
    }
}
