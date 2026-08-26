<?php

namespace App\Http\Controllers\Api\Pastor;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\GeneratedContent;
use App\Services\BibleTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Relecture des contenus generes par l'IA.
 *
 * C'est le point 3 de l'ADR-002 : rien n'est servi aux membres avant d'avoir
 * ete relu. Sur un sujet religieux, une approximation doctrinale coute la
 * confiance de la communaute — c'est une contrainte produit, pas un confort.
 */
class ContentReviewController extends Controller
{
    public function __construct(private readonly BibleTextService $bible)
    {
    }

    /** GET /api/v1/pastor/contents — la file de relecture. */
    public function index(Request $request): JsonResponse
    {
        $contents = GeneratedContent::where('status', GeneratedContent::STATUS_PENDING)
            ->when($request->input('kind'), fn ($q, $kind) => $q->where('kind', $kind))
            ->oldest()
            ->paginate(15);

        return response()->json($contents);
    }

    /**
     * GET /api/v1/pastor/contents/{content}
     *
     * Renvoie le passage biblique avec le contenu genere : on ne peut pas
     * relire serieusement une meditation sur Jean 3 sans Jean 3 sous les yeux.
     */
    public function show(GeneratedContent $content): JsonResponse
    {
        return response()->json([
            'content' => $content,
            'passage' => $this->passageFor($content),
        ]);
    }

    /**
     * PUT /api/v1/pastor/contents/{content}
     *
     * Le pasteur peut corriger avant d'approuver. C'est important : ecarter un
     * texte presque juste serait du gaspillage, alors qu'en corriger une
     * phrase suffit souvent.
     */
    public function update(Request $request, GeneratedContent $content): JsonResponse
    {
        $data = $request->validate([
            'payload' => ['required', 'array'],
        ]);

        $content->update(['payload' => $data['payload']]);

        return response()->json([
            'message' => 'Contenu corrige. Il reste en attente d\'approbation.',
            'content' => $content->refresh(),
        ]);
    }

    /** POST /api/v1/pastor/contents/{content}/approve */
    public function approve(Request $request, GeneratedContent $content): JsonResponse
    {
        $content->update([
            'status' => GeneratedContent::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Contenu approuve : il est desormais servi a toute la communaute.',
            'content' => $content->refresh(),
        ]);
    }

    /** POST /api/v1/pastor/contents/{content}/reject */
    public function reject(Request $request, GeneratedContent $content): JsonResponse
    {
        $content->update([
            'status' => GeneratedContent::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Contenu ecarte. Il ne sera pas regenere automatiquement.',
            'content' => $content->refresh(),
        ]);
    }

    // ------------------------------------------------------------------ prive

    /** @return array<string, mixed>|null */
    private function passageFor(GeneratedContent $content): ?array
    {
        // Les biographies ne portent pas sur un chapitre : reference = un slug.
        if (! str_contains($content->reference, '-')) {
            return null;
        }

        [$bookId, $chapter] = array_map('intval', explode('-', $content->reference, 2));

        $book = Book::find($bookId);

        if (! $book || $chapter < 1) {
            return null;
        }

        return [
            'book' => $book->name,
            'chapter' => $chapter,
            'verses' => $this->bible->chapter($book, $chapter)
                ->map(fn ($v) => ['number' => $v->number, 'text' => $v->text]),
        ];
    }
}
