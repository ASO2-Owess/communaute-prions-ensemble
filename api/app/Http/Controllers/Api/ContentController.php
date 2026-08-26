<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\GeneratedContent;
use App\Services\AiRelayService;
use App\Services\BibleTextService;
use App\Support\Prompts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contenus generes par l'IA, cote membre.
 *
 * L'application n'appelle jamais l'IA elle-meme : elle appelle ces routes.
 * La cle reste sur le serveur (ADR-002), et les prompts sont ancres dans le
 * texte biblique reel (ADR-008).
 */
class ContentController extends Controller
{
    public function __construct(
        private readonly AiRelayService $ai,
        private readonly BibleTextService $bible,
    ) {
    }

    /** GET /api/v1/contents/meditation/{book}/{chapter} */
    public function meditation(Request $request, Book $book, int $chapter): JsonResponse
    {
        if ($error = $this->rejectInvalidChapter($book, $chapter)) {
            return $error;
        }

        $result = $this->ai->fetch(
            $request->user(),
            GeneratedContent::KIND_MEDITATION,
            GeneratedContent::chapterReference($book->id, $chapter),
            Prompts::meditation($book->name, $chapter, $this->bible->chapterText($book, $chapter))
        );

        return $this->respond($result, $request);
    }

    /**
     * GET /api/v1/contents/chapter-quiz/{book}/{chapter}
     *
     * Debloque par l'ADR-008 : sans le texte cote serveur, impossible de
     * garantir des questions dont la reponse est verifiable dans le chapitre.
     */
    public function chapterQuiz(Request $request, Book $book, int $chapter): JsonResponse
    {
        if ($error = $this->rejectInvalidChapter($book, $chapter)) {
            return $error;
        }

        $result = $this->ai->fetch(
            $request->user(),
            GeneratedContent::KIND_CHAPTER_QUIZ,
            GeneratedContent::chapterReference($book->id, $chapter),
            Prompts::chapterQuiz($book->name, $chapter, $this->bible->chapterText($book, $chapter))
        );

        return $this->respond($result, $request);
    }

    /** GET /api/v1/contents/biography/{name} */
    public function biography(Request $request, string $name): JsonResponse
    {
        $slug = str($name)->lower()->slug()->toString();

        $result = $this->ai->fetch(
            $request->user(),
            GeneratedContent::KIND_BIOGRAPHY,
            $slug,
            Prompts::biography($name, $request->string('category')->toString() ?: 'personnage biblique')
        );

        return $this->respond($result, $request);
    }

    // ------------------------------------------------------------------ prive

    private function rejectInvalidChapter(Book $book, int $chapter): ?JsonResponse
    {
        if (! $book->hasChapter($chapter)) {
            return response()->json([
                'message' => "Le livre {$book->name} compte {$book->chapter_count} chapitres.",
            ], 422);
        }

        if (! $this->bible->hasText($book, $chapter)) {
            // Le seeder n'a pas tourne, ou l'import est incomplet. On le dit
            // franchement plutot que d'envoyer un prompt vide a l'IA.
            return response()->json([
                'message' => 'Le texte de ce chapitre est absent du serveur.',
            ], 503);
        }

        return null;
    }

    /**
     * @param  array{status: string, content: array|null, message: string|null}  $result
     */
    private function respond(array $result, Request $request): JsonResponse
    {
        $status = match ($result['status']) {
            'ready' => 200,
            'pending_review' => 202,  // accepte, traitement en cours
            'quota_exceeded' => 429,
            default => 503,
        };

        return response()->json([
            ...$result,
            'quota' => [
                'used_today' => $this->ai->usageToday($request->user()),
                'daily_limit' => config('ai.daily_quota_per_user'),
            ],
        ], $status);
    }
}
