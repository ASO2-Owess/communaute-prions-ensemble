<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Moyens de don. En base et non dans le code : un numero change, et on ne
 * republie pas une application sur les magasins pour ca.
 */
class DonationMethodSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->rows() as $row) {
            DB::table('donation_methods')->updateOrInsert(
                ['provider' => $row['provider']],
                [...$row, 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return [
            ['provider' => 'wave', 'label' => 'Wave', 'phone' => '+225 07 09 13 85 75', 'note' => null, 'position' => 1],
            ['provider' => 'orange-money', 'label' => 'Orange Money', 'phone' => '+225 07 09 13 85 75', 'note' => null, 'position' => 2],
        ];
    }
}
