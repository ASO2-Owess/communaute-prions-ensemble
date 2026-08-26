<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Plans de lecture.
 *
 * Deux plans au depart :
 *   - « Douze grands moments » : le plan du prototype, une porte d'entree
 *     courte pour quelqu'un qui n'a jamais lu la Bible.
 *   - « La Bible en un an » : 365 jours, calcules a partir de la table books.
 *
 * Le plan annuel n'est PAS ecrit en dur : il est reparti par calcul sur les
 * 1 189 chapitres. Ecrire 365 lignes a la main aurait garanti des erreurs, et
 * les rendrait impossibles a corriger sans tout relire.
 */
class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->douzeMoments();
        $this->bibleEnUnAn();
    }

    // ------------------------------------------------------- plan court

    private function douzeMoments(): void
    {
        $planId = $this->plan(
            'douze-moments',
            'Douze grands moments',
            'Douze chapitres pour parcourir le fil de l\'histoire biblique, '
                . 'de la Creation a la resurrection. Ideal pour commencer.',
            12,
            1
        );

        foreach ($this->momentsRows() as $row) {
            $book = Book::where('name', $row['book'])->first();

            if (! $book) {
                continue;
            }

            DB::table('reading_plan_days')->updateOrInsert(
                ['reading_plan_id' => $planId, 'day' => $row['day'], 'position' => 1],
                [
                    'book_id' => $book->id,
                    'chapter_from' => $row['chapter'],
                    'chapter_to' => $row['chapter'],
                    'label' => $row['label'],
                ]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function momentsRows(): array
    {
        return [
            ['day' => 1, 'book' => 'Genèse', 'chapter' => 1, 'label' => 'La Création'],
            ['day' => 2, 'book' => 'Genèse', 'chapter' => 3, 'label' => 'La chute'],
            ['day' => 3, 'book' => 'Exode', 'chapter' => 3, 'label' => 'Le buisson ardent'],
            ['day' => 4, 'book' => 'Exode', 'chapter' => 20, 'label' => 'Les Dix Commandements'],
            ['day' => 5, 'book' => 'Josué', 'chapter' => 1, 'label' => 'Fortifie-toi et prends courage'],
            ['day' => 6, 'book' => 'Psaumes', 'chapter' => 23, 'label' => 'L\'Éternel est mon berger'],
            ['day' => 7, 'book' => 'Ésaïe', 'chapter' => 6, 'label' => 'La vision d\'Ésaïe'],
            ['day' => 8, 'book' => 'Luc', 'chapter' => 1, 'label' => 'L\'Annonciation'],
            ['day' => 9, 'book' => 'Luc', 'chapter' => 2, 'label' => 'La naissance de Jésus'],
            ['day' => 10, 'book' => 'Jean', 'chapter' => 1, 'label' => 'La Parole faite chair'],
            ['day' => 11, 'book' => 'Matthieu', 'chapter' => 5, 'label' => 'Le Sermon sur la montagne'],
            ['day' => 12, 'book' => 'Matthieu', 'chapter' => 28, 'label' => 'La résurrection'],
        ];
    }

    // ------------------------------------------------------- plan annuel

    private function bibleEnUnAn(): void
    {
        $jours = 365;

        $planId = $this->plan(
            'bible-en-un-an',
            'La Bible en un an',
            'Les 66 livres en 365 jours, dans l\'ordre canonique. '
                . 'Environ trois chapitres par jour.',
            $jours,
            2
        );

        DB::table('reading_plan_days')->where('reading_plan_id', $planId)->delete();

        // Suite continue des 1 189 chapitres : (livre, chapitre) a la position N.
        $suite = [];
        foreach (Book::orderBy('position')->get() as $book) {
            for ($c = 1; $c <= $book->chapter_count; $c++) {
                $suite[] = [$book->id, $c];
            }
        }

        $total = count($suite);
        $lignes = [];

        for ($jour = 1; $jour <= $jours; $jour++) {
            // Repartition proportionnelle : evite d'accumuler l'arrondi et de
            // se retrouver avec un dernier jour surcharge.
            $debut = (int) floor(($jour - 1) * $total / $jours);
            $fin = (int) floor($jour * $total / $jours) - 1;

            $position = 1;
            $i = $debut;

            while ($i <= $fin) {
                $bookId = $suite[$i][0];
                $premier = $suite[$i][1];
                $dernier = $premier;

                // On regroupe les chapitres consecutifs d'un meme livre en une
                // seule ligne ; un jour qui chevauche deux livres en produit
                // deux, d'ou .
                while ($i + 1 <= $fin && $suite[$i + 1][0] === $bookId) {
                    $i++;
                    $dernier = $suite[$i][1];
                }

                $lignes[] = [
                    'reading_plan_id' => $planId,
                    'day' => $jour,
                    'book_id' => $bookId,
                    'chapter_from' => $premier,
                    'chapter_to' => $dernier,
                    'label' => null,
                    'position' => $position,
                ];

                $position++;
                $i++;
            }
        }

        foreach (array_chunk($lignes, 500) as $paquet) {
            DB::table('reading_plan_days')->insert($paquet);
        }

        $this->command?->info('Plan annuel : ' . count($lignes) . ' entrees pour ' . $jours . ' jours.');
    }

    // ------------------------------------------------------------- outils

    private function plan(string $slug, string $name, string $description, int $days, int $position): int
    {
        DB::table('reading_plans')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'days_count' => $days,
                'published' => true,
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return (int) DB::table('reading_plans')->where('slug', $slug)->value('id');
    }
}
