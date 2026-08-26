<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importe les 31 170 versets depuis database/data/bible-lsg.json.
 *
 * Format du fichier, volontairement compact (4,3 Mo) :
 *   { "<book_id>": { "<chapitre>": ["verset 1", "verset 2", ...] } }
 *
 * La position dans le tableau donne le numero du verset : stocker "1", "2",
 * "3"... a cote de chaque texte aurait alourdi le fichier pour rien.
 */
class VerseSeeder extends Seeder
{
    /** 31 170 lignes d'un coup saturent la memoire et depassent max_allowed_packet. */
    private const CHUNK = 1000;

    public function run(): void
    {
        $path = database_path('data/bible-lsg.json');

        if (! is_file($path)) {
            $this->command?->error("Fichier introuvable : {$path}");

            return;
        }

        $bible = json_decode(file_get_contents($path), true);

        if (! is_array($bible)) {
            $this->command?->error('bible-lsg.json illisible.');

            return;
        }

        // On vide avant de reimporter : le seeder doit pouvoir etre relance
        // sans creer de doublons ni echouer sur la cle primaire.
        DB::table('verses')->delete();

        $buffer = [];
        $total = 0;

        foreach ($bible as $bookId => $chapters) {
            foreach ($chapters as $chapter => $verses) {
                foreach ($verses as $index => $text) {
                    $buffer[] = [
                        'book_id' => (int) $bookId,
                        'chapter' => (int) $chapter,
                        'number' => $index + 1,
                        'text' => $text,
                    ];

                    if (count($buffer) >= self::CHUNK) {
                        DB::table('verses')->insert($buffer);
                        $total += count($buffer);
                        $buffer = [];
                    }
                }
            }
        }

        if ($buffer !== []) {
            DB::table('verses')->insert($buffer);
            $total += count($buffer);
        }

        $this->command?->info("Versets importes : {$total}");

        if ($total !== 31170) {
            $this->command?->warn("Attendu : 31 170 versets. Verifie bible-lsg.json.");
        }
    }
}
