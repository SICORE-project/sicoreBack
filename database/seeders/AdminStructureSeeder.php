<?php

namespace Database\Seeders;

use App\Models\Parametrage\LieuService;
use Illuminate\Database\Seeder;

class AdminStructureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'CI' => 'Cellule Informatique',
            'DAGE' => 'Direction de l’Administration générale et de l’Équipement',
        ] as $code => $libelle) {
            LieuService::firstOrCreate(['code' => $code], [
                'libelle' => $libelle,
                'type' => $code,
                'perimetre' => 'national',
                'est_actif' => true,
            ]);
        }
    }
}
