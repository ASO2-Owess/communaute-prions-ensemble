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
            BookSeeder::class,   // 66 livres  — doit passer avant VerseSeeder
            VerseSeeder::class,  // 31 170 versets
        ]);
    }
}
