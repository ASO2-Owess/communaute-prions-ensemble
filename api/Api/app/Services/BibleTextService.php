<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Support\Collection;

/**
 * Acces au texte biblique cote serveur.
 *
 * Une seule porte d'entree vers la table verses : si demain le texte venait
 * d'ailleurs (autre traduction, fichier, service externe), un seul fichier
 * changerait.
 */
class BibleTextService
{
    /** Longueur maximale envoyee a l'IA. Au-dela, on tronque proprement. */
    private const MAX_PROMPT_CHARS = 12000;

    /** @return Collection<int, Verse> */
    public function chapter(Book $book, int $chapter): Collection
    {
        return Verse::where('book_id', $book->id)
            ->where('chapter', $chapter)
            ->orderBy('number')
            ->get();
    }

    /**
     * Le chapitre en texte brut, numeros inclus — la forme attendue par les
     * prompts : "1. Au commencement... 2. La terre etait informe..."
     */
    public function chapterText(Book $book, int $chapter): string
    {
        $text = $this->chapter($book, $chapter)
            ->map(fn (Verse $v) => "{$v->number}. {$v->text}")
            ->implode("\n");

        return $this->truncate($text);
    }

    public function hasText(Book $book, int $chapter): bool
    {
        return Verse::where('book_id', $book->id)
            ->where('chapter', $chapter)
            ->exists();
    }

    public function verseCount(Book $book, int $chapter): int
    {
        return Verse::where('book_id', $book->id)
            ->where('chapter', $chapter)
            ->count();
    }

    /**
     * Coupe a la limite, sur une fin de verset plutot qu'au milieu d'un mot.
     *
     * Les Psaumes 119 (176 versets) depassent la limite : mieux vaut une
     * troncature nette et signalee qu'une phrase coupee en deux, qui
     * induirait le modele en erreur.
     */
    private function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_PROMPT_CHARS) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::MAX_PROMPT_CHARS);
        $lastBreak = mb_strrpos($cut, "\n");

        if ($lastBreak !== false) {
            $cut = mb_substr($cut, 0, $lastBreak);
        }

        return $cut . "\n[Texte tronque : chapitre trop long pour etre transmis en entier.]";
    }
}
