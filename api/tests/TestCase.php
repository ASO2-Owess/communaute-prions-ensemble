<?php

namespace Tests;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Charge les 66 livres.
     *
     * On ne charge JAMAIS les 31 170 versets dans un test : ce serait plusieurs
     * secondes par test pour une donnee dont on n'utilise qu'un chapitre. Les
     * tests qui ont besoin de texte l'inserent eux-memes avec seedVerses().
     */
    protected function seedBooks(): void
    {
        $this->seed(BookSeeder::class);
    }

    /** Insere un chapitre fictif mais structurellement correct. */
    protected function seedVerses(int $bookId, int $chapter, int $count = 5): void
    {
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'book_id' => $bookId,
                'chapter' => $chapter,
                'number' => $i,
                'text' => "Texte du verset {$i}.",
            ];
        }

        DB::table('verses')->insert($rows);
    }

    /**
     * fresh() relit la ligne depuis la base : le modele porte alors les
     * valeurs reellement enregistrees, y compris celles posees par la base.
     * Sans ca, un test travaillerait sur un objet incomplet — et passerait ou
     * echouerait pour une mauvaise raison.
     */
    protected function member(array $attributes = []): User
    {
        return User::factory()->create($attributes)->fresh();
    }

    protected function pastor(array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'role' => User::ROLE_PASTOR])->fresh();
    }

    /** Marque tous les chapitres comme lus — pour tester la fin de cycle. */
    protected function readWholeBible(User $user): void
    {
        $rows = [];
        $now = now();
        $cycle = $user->reading_cycle;

        foreach (Book::all() as $book) {
            for ($chapter = 1; $chapter <= $book->chapter_count; $chapter++) {
                $rows[] = [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'chapter' => $chapter,
                    'cycle' => $cycle,
                    'read_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('readings')->insert($chunk);
        }
    }
}
