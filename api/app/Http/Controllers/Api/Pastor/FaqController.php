<?php

namespace App\Http\Controllers\Api\Pastor;

use App\Http\Controllers\Controller;
use App\Models\FaqEntry;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion de la FAQ pastorale (ADR-007).
 *
 * C'est la parade au risque n°1 du projet : un seul répondant pour plus de
 * 2 000 membres. Beaucoup de questions se répètent ; transformer une réponse
 * déjà écrite en entrée de FAQ, c'est répondre une fois pour tous.
 */
class FaqController extends Controller
{
    /** GET /api/v1/pastor/faq — publiées et brouillons. */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FaqEntry::orderBy('position')->orderByDesc('id')->paginate(30),
        ]);
    }

    /**
     * POST /api/v1/pastor/faq
     *
     * Peut naître d'une vraie question (`source_question_id`) : le pasteur
     * reformule et anonymise avant de publier. Le texte de la FAQ est TOUJOURS
     * réécrit — on ne recopie jamais tel quel ce qu'un membre a confié.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:10', 'max:500'],
            'answer' => ['required', 'string', 'min:20', 'max:8000'],
            'topic' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'source_question_id' => ['nullable', 'integer', 'exists:questions,id'],
        ]);

        $entry = FaqEntry::create([
            ...$data,
            'author_id' => $request->user()->id,
            'published' => $request->boolean('published'),
        ]);

        return response()->json([
            'message' => $entry->published
                ? 'Entrée publiée dans la FAQ.'
                : 'Brouillon enregistré.',
            'data' => $entry,
        ], 201);
    }

    /** PUT /api/v1/pastor/faq/{entry} */
    public function update(Request $request, FaqEntry $entry): JsonResponse
    {
        $data = $request->validate([
            'question' => ['sometimes', 'string', 'min:10', 'max:500'],
            'answer' => ['sometimes', 'string', 'min:20', 'max:8000'],
            'topic' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
        ]);

        $entry->update($data);

        return response()->json(['message' => 'Entrée mise à jour.', 'data' => $entry->refresh()]);
    }

    /** DELETE /api/v1/pastor/faq/{entry} */
    public function destroy(FaqEntry $entry): JsonResponse
    {
        $entry->delete();

        return response()->json(['message' => 'Entrée supprimée.']);
    }

    /**
     * GET /api/v1/pastor/faq/suggestions
     *
     * Les thèmes qui reviennent le plus dans les questions en attente : le
     * pasteur voit d'un coup d'œil ce qui mériterait une entrée de FAQ plutôt
     * que trente réponses individuelles.
     */
    public function suggestions(): JsonResponse
    {
        return response()->json([
            'by_topic' => Question::awaiting()
                ->selectRaw("COALESCE(topic, 'autre') as topic, COUNT(*) as total")
                ->groupBy('topic')
                ->orderByDesc('total')
                ->get(),

            'answered_without_faq' => Question::where('status', Question::STATUS_ANSWERED)
                ->whereDoesntHave('faqEntry')
                ->with('answer:id,question_id,body')
                ->latest()
                ->limit(20)
                ->get(['id', 'topic', 'created_at'])
                ->map(fn ($q) => [
                    'question_id' => $q->id,
                    'topic' => $q->topic,
                    'answered_at' => $q->created_at?->toDateString(),
                ]),
        ]);
    }
}
