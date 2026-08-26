<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Versets d'encouragement — le « verset du jour » de l'accueil.
 *
 * Ils vivaient en dur dans l'application : en ajouter un obligeait a republier.
 * Ici, le pasteur pourra en ajouter depuis le back-office.
 */
class EncouragementSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('encouragements')->insertOrIgnore(array_map(
            fn ($e) => [...$e, 'created_at' => $now, 'updated_at' => $now],
            $this->rows()
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return [
            ['text' => 'Ne crains rien, car je suis avec toi; ne promène pas des regards inquiets, car je suis ton Dieu; je te fortifie, je viens à ton secours.', 'reference' => 'Ésaïe 41.10', 'published' => true],
            ['text' => 'Je puis tout par celui qui me fortifie.', 'reference' => 'Philippiens 4.13', 'published' => true],
            ['text' => 'Car je connais les projets que j\'ai formés sur vous, dit l\'Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l\'espérance.', 'reference' => 'Jérémie 29.11', 'published' => true],
            ['text' => 'L\'Éternel est mon berger: je ne manquerai de rien.', 'reference' => 'Psaume 23.1', 'published' => true],
            ['text' => 'Fortifie-toi et prends courage, ne t\'effraie point et ne t\'épouvante point; car l\'Éternel, ton Dieu, est avec toi.', 'reference' => 'Josué 1.9', 'published' => true],
            ['text' => 'Que tout ce que vous faites se fasse avec amour.', 'reference' => '1 Corinthiens 16.14', 'published' => true],
            ['text' => 'L\'Éternel lui-même marchera devant toi, il sera lui-même avec toi, il ne te délaissera point, il ne t\'abandonnera point.', 'reference' => 'Deutéronome 31.8', 'published' => true],
            ['text' => 'Remets ton sort à l\'Éternel, mets en lui ta confiance, et il agira.', 'reference' => 'Psaume 37.5', 'published' => true],
        ];
    }
}
