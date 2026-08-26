<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Donnees de reference : indispensables en developpement comme en
            // production. Ce ne sont pas des donnees de test.
            BookSeeder::class,           // 66 livres — avant tout le reste
            VerseSeeder::class,          // 31 170 versets
            EncouragementSeeder::class,
            BibleFigureSeeder::class,
            QuizQuestionSeeder::class,
            DonationMethodSeeder::class,
            ReadingPlanSeeder::class,    // depend de BookSeeder
        ]);
    }
}
