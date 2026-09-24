<?php

namespace Database\Seeders;

use App\Models\Parametrage\LieuService;
use Illuminate\Database\Seeder;

class DecpcStructureSeeder extends Seeder
{
    public function run(): void
    {
        LieuService::firstOrCreate(['type' => 'DECPC'], [
            'code' => 'DECPC',
            'libelle' => 'Direction des Examens, Concours Professionnels et Certifications',
            'perimetre' => 'national',
            'est_actif' => true,
        ]);
    }
}
