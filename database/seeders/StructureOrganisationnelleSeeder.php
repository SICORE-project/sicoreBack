<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parametrage\LieuService;

class StructureOrganisationnelleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'code' => 'DG',
                'libelle' => 'Direction Générale',
                'type' => 'DRH',
                'perimetre' => 'national',
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
            ],
            [
                'code' => 'DRH',
                'libelle' => 'Direction des Ressources Humaines',
                'type' => 'DRH',
                'perimetre' => 'national',
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
            ],
            [
                'code' => 'DF',
                'libelle' => 'Direction Financière',
                'type' => 'DAGE',
                'perimetre' => 'national',
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
            ],
            [
                'code' => 'SI',
                'libelle' => 'Service Informatique',
                'type' => 'DECPC',
                'perimetre' => 'national',
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
            ],
            [
                'code' => 'SP',
                'libelle' => 'Service Paie',
                'type' => 'DAGE',
                'perimetre' => 'national',
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
            ],
        ] as $structure) {
            LieuService::withTrashed()->updateOrCreate(['code' => $structure['code']], $structure + ['deleted_at' => null]);
        }

        $this->command->info('✅ Structures organisationnelles insérées avec succès !');
    }
}
