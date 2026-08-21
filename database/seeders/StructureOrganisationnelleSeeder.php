<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureOrganisationnelleSeeder extends Seeder
{
    public function run(): void
    {
        // Éviter les doublons
        if (DB::table('structures_organisationnelles')->count() > 0) {
            $this->command->info('Les structures existent déjà. Aucune insertion effectuée.');
            return;
        }

        DB::table('structures_organisationnelles')->insert([
            [
                'code' => 'DG',
                'libelle' => 'Direction Générale',
                'type' => 'direction',
                'parent_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DRH',
                'libelle' => 'Direction des Ressources Humaines',
                'type' => 'direction',
                'parent_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DF',
                'libelle' => 'Direction Financière',
                'type' => 'direction',
                'parent_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SI',
                'libelle' => 'Service Informatique',
                'type' => 'service',
                'parent_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SP',
                'libelle' => 'Service Paie',
                'type' => 'service',
                'parent_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'est_actif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ Structures organisationnelles insérées avec succès !');
    }
}
