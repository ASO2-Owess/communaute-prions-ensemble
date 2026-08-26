<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les 66 livres de la Bible, dans l'ordre canonique.
 *
 * Les nombres de chapitres sont ceux du texte Louis Segond embarque dans
 * l'application (1 189 chapitres au total). Ils doivent correspondre
 * exactement a ce que le client affiche : c'est ce qui permet au serveur de
 * refuser une reference invalide et de detecter la fin d'un cycle de lecture.
 *
 * Ce seeder est idempotent : le relancer met a jour sans dupliquer.
 */
class BookSeeder extends Seeder
{
    /** Sert a valider qu'un cycle de lecture est complet. */
    public const TOTAL_CHAPTERS = 1189;

    public function run(): void
    {
        DB::table('books')->upsert(
            $this->books(),
            ['id'],
            ['name', 'slug', 'testament', 'chapter_count', 'position']
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function books(): array
    {
        return [
            // ---------------------------------------------- Ancien Testament
            ['id' => 1,  'name' => 'Genèse',                 'slug' => 'genese',                 'testament' => 'AT', 'chapter_count' => 50,  'position' => 1],
            ['id' => 2,  'name' => 'Exode',                  'slug' => 'exode',                  'testament' => 'AT', 'chapter_count' => 40,  'position' => 2],
            ['id' => 3,  'name' => 'Lévitique',              'slug' => 'levitique',              'testament' => 'AT', 'chapter_count' => 27,  'position' => 3],
            ['id' => 4,  'name' => 'Nombres',                'slug' => 'nombres',                'testament' => 'AT', 'chapter_count' => 36,  'position' => 4],
            ['id' => 5,  'name' => 'Deutéronome',            'slug' => 'deuteronome',            'testament' => 'AT', 'chapter_count' => 34,  'position' => 5],
            ['id' => 6,  'name' => 'Josué',                  'slug' => 'josue',                  'testament' => 'AT', 'chapter_count' => 24,  'position' => 6],
            ['id' => 7,  'name' => 'Juges',                  'slug' => 'juges',                  'testament' => 'AT', 'chapter_count' => 21,  'position' => 7],
            ['id' => 8,  'name' => 'Ruth',                   'slug' => 'ruth',                   'testament' => 'AT', 'chapter_count' => 4,   'position' => 8],
            ['id' => 9,  'name' => '1 Samuel',               'slug' => '1-samuel',               'testament' => 'AT', 'chapter_count' => 31,  'position' => 9],
            ['id' => 10, 'name' => '2 Samuel',               'slug' => '2-samuel',               'testament' => 'AT', 'chapter_count' => 24,  'position' => 10],
            ['id' => 11, 'name' => '1 Rois',                 'slug' => '1-rois',                 'testament' => 'AT', 'chapter_count' => 22,  'position' => 11],
            ['id' => 12, 'name' => '2 Rois',                 'slug' => '2-rois',                 'testament' => 'AT', 'chapter_count' => 25,  'position' => 12],
            ['id' => 13, 'name' => '1 Chroniques',           'slug' => '1-chroniques',           'testament' => 'AT', 'chapter_count' => 29,  'position' => 13],
            ['id' => 14, 'name' => '2 Chroniques',           'slug' => '2-chroniques',           'testament' => 'AT', 'chapter_count' => 36,  'position' => 14],
            ['id' => 15, 'name' => 'Esdras',                 'slug' => 'esdras',                 'testament' => 'AT', 'chapter_count' => 10,  'position' => 15],
            ['id' => 16, 'name' => 'Néhémie',                'slug' => 'nehemie',                'testament' => 'AT', 'chapter_count' => 13,  'position' => 16],
            ['id' => 17, 'name' => 'Esther',                 'slug' => 'esther',                 'testament' => 'AT', 'chapter_count' => 10,  'position' => 17],
            ['id' => 18, 'name' => 'Job',                    'slug' => 'job',                    'testament' => 'AT', 'chapter_count' => 42,  'position' => 18],
            ['id' => 19, 'name' => 'Psaumes',                'slug' => 'psaumes',                'testament' => 'AT', 'chapter_count' => 150, 'position' => 19],
            ['id' => 20, 'name' => 'Proverbes',              'slug' => 'proverbes',              'testament' => 'AT', 'chapter_count' => 31,  'position' => 20],
            ['id' => 21, 'name' => 'Ecclésiaste',            'slug' => 'ecclesiaste',            'testament' => 'AT', 'chapter_count' => 12,  'position' => 21],
            ['id' => 22, 'name' => 'Cantique des Cantiques', 'slug' => 'cantique-des-cantiques', 'testament' => 'AT', 'chapter_count' => 8,   'position' => 22],
            ['id' => 23, 'name' => 'Ésaïe',                  'slug' => 'esaie',                  'testament' => 'AT', 'chapter_count' => 66,  'position' => 23],
            ['id' => 24, 'name' => 'Jérémie',                'slug' => 'jeremie',                'testament' => 'AT', 'chapter_count' => 52,  'position' => 24],
            ['id' => 25, 'name' => 'Lamentations',           'slug' => 'lamentations',           'testament' => 'AT', 'chapter_count' => 5,   'position' => 25],
            ['id' => 26, 'name' => 'Ézéchiel',               'slug' => 'ezechiel',               'testament' => 'AT', 'chapter_count' => 48,  'position' => 26],
            ['id' => 27, 'name' => 'Daniel',                 'slug' => 'daniel',                 'testament' => 'AT', 'chapter_count' => 12,  'position' => 27],
            ['id' => 28, 'name' => 'Osée',                   'slug' => 'osee',                   'testament' => 'AT', 'chapter_count' => 14,  'position' => 28],
            ['id' => 29, 'name' => 'Joël',                   'slug' => 'joel',                   'testament' => 'AT', 'chapter_count' => 3,   'position' => 29],
            ['id' => 30, 'name' => 'Amos',                   'slug' => 'amos',                   'testament' => 'AT', 'chapter_count' => 9,   'position' => 30],
            ['id' => 31, 'name' => 'Abdias',                 'slug' => 'abdias',                 'testament' => 'AT', 'chapter_count' => 1,   'position' => 31],
            ['id' => 32, 'name' => 'Jonas',                  'slug' => 'jonas',                  'testament' => 'AT', 'chapter_count' => 4,   'position' => 32],
            ['id' => 33, 'name' => 'Michée',                 'slug' => 'michee',                 'testament' => 'AT', 'chapter_count' => 7,   'position' => 33],
            ['id' => 34, 'name' => 'Nahum',                  'slug' => 'nahum',                  'testament' => 'AT', 'chapter_count' => 3,   'position' => 34],
            ['id' => 35, 'name' => 'Habacuc',                'slug' => 'habacuc',                'testament' => 'AT', 'chapter_count' => 3,   'position' => 35],
            ['id' => 36, 'name' => 'Sophonie',               'slug' => 'sophonie',               'testament' => 'AT', 'chapter_count' => 3,   'position' => 36],
            ['id' => 37, 'name' => 'Aggée',                  'slug' => 'aggee',                  'testament' => 'AT', 'chapter_count' => 2,   'position' => 37],
            ['id' => 38, 'name' => 'Zacharie',               'slug' => 'zacharie',               'testament' => 'AT', 'chapter_count' => 14,  'position' => 38],
            ['id' => 39, 'name' => 'Malachie',               'slug' => 'malachie',               'testament' => 'AT', 'chapter_count' => 4,   'position' => 39],

            // -------------------------------------------- Nouveau Testament
            ['id' => 40, 'name' => 'Matthieu',               'slug' => 'matthieu',               'testament' => 'NT', 'chapter_count' => 28,  'position' => 40],
            ['id' => 41, 'name' => 'Marc',                   'slug' => 'marc',                   'testament' => 'NT', 'chapter_count' => 16,  'position' => 41],
            ['id' => 42, 'name' => 'Luc',                    'slug' => 'luc',                    'testament' => 'NT', 'chapter_count' => 24,  'position' => 42],
            ['id' => 43, 'name' => 'Jean',                   'slug' => 'jean',                   'testament' => 'NT', 'chapter_count' => 21,  'position' => 43],
            ['id' => 44, 'name' => 'Actes',                  'slug' => 'actes',                  'testament' => 'NT', 'chapter_count' => 28,  'position' => 44],
            ['id' => 45, 'name' => 'Romains',                'slug' => 'romains',                'testament' => 'NT', 'chapter_count' => 16,  'position' => 45],
            ['id' => 46, 'name' => '1 Corinthiens',          'slug' => '1-corinthiens',          'testament' => 'NT', 'chapter_count' => 16,  'position' => 46],
            ['id' => 47, 'name' => '2 Corinthiens',          'slug' => '2-corinthiens',          'testament' => 'NT', 'chapter_count' => 13,  'position' => 47],
            ['id' => 48, 'name' => 'Galates',                'slug' => 'galates',                'testament' => 'NT', 'chapter_count' => 6,   'position' => 48],
            ['id' => 49, 'name' => 'Éphésiens',              'slug' => 'ephesiens',              'testament' => 'NT', 'chapter_count' => 6,   'position' => 49],
            ['id' => 50, 'name' => 'Philippiens',            'slug' => 'philippiens',            'testament' => 'NT', 'chapter_count' => 4,   'position' => 50],
            ['id' => 51, 'name' => 'Colossiens',             'slug' => 'colossiens',             'testament' => 'NT', 'chapter_count' => 4,   'position' => 51],
            ['id' => 52, 'name' => '1 Thessaloniciens',      'slug' => '1-thessaloniciens',      'testament' => 'NT', 'chapter_count' => 5,   'position' => 52],
            ['id' => 53, 'name' => '2 Thessaloniciens',      'slug' => '2-thessaloniciens',      'testament' => 'NT', 'chapter_count' => 3,   'position' => 53],
            ['id' => 54, 'name' => '1 Timothée',             'slug' => '1-timothee',             'testament' => 'NT', 'chapter_count' => 6,   'position' => 54],
            ['id' => 55, 'name' => '2 Timothée',             'slug' => '2-timothee',             'testament' => 'NT', 'chapter_count' => 4,   'position' => 55],
            ['id' => 56, 'name' => 'Tite',                   'slug' => 'tite',                   'testament' => 'NT', 'chapter_count' => 3,   'position' => 56],
            ['id' => 57, 'name' => 'Philémon',               'slug' => 'philemon',               'testament' => 'NT', 'chapter_count' => 1,   'position' => 57],
            ['id' => 58, 'name' => 'Hébreux',                'slug' => 'hebreux',                'testament' => 'NT', 'chapter_count' => 13,  'position' => 58],
            ['id' => 59, 'name' => 'Jacques',                'slug' => 'jacques',                'testament' => 'NT', 'chapter_count' => 5,   'position' => 59],
            ['id' => 60, 'name' => '1 Pierre',               'slug' => '1-pierre',               'testament' => 'NT', 'chapter_count' => 5,   'position' => 60],
            ['id' => 61, 'name' => '2 Pierre',               'slug' => '2-pierre',               'testament' => 'NT', 'chapter_count' => 3,   'position' => 61],
            ['id' => 62, 'name' => '1 Jean',                 'slug' => '1-jean',                 'testament' => 'NT', 'chapter_count' => 5,   'position' => 62],
            ['id' => 63, 'name' => '2 Jean',                 'slug' => '2-jean',                 'testament' => 'NT', 'chapter_count' => 1,   'position' => 63],
            ['id' => 64, 'name' => '3 Jean',                 'slug' => '3-jean',                 'testament' => 'NT', 'chapter_count' => 1,   'position' => 64],
            ['id' => 65, 'name' => 'Jude',                   'slug' => 'jude',                   'testament' => 'NT', 'chapter_count' => 1,   'position' => 65],
            ['id' => 66, 'name' => 'Apocalypse',             'slug' => 'apocalypse',             'testament' => 'NT', 'chapter_count' => 22,  'position' => 66],
        ];
    }
}
