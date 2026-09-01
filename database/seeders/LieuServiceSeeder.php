<?php

namespace Database\Seeders;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\LieuService;
use Illuminate\Database\Seeder;

class LieuServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer 'type' et 'perimetre'
        foreach (['DRH', 'DAGE', 'DECPC'] as $code) {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $code],
                [
                    'libelle' => $code,
                    // 'type' => $code, // ❌ À SUPPRIMER
                    // 'perimetre' => 'national', // ❌ À SUPPRIMER
                    'est_actif' => true,
                    'deleted_at' => null
                ]
            );
        }

        Ia::query()->each(function (Ia $ia): void {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $ia->code],
                [
                    'libelle' => $ia->libelle,
                    // 'type' => 'IA', // ❌ À SUPPRIMER
                    // 'perimetre' => 'regional', // ❌ À SUPPRIMER
                    'ia_id' => $ia->id,
                    'ief_id' => null,
                    'est_actif' => true,
                    'deleted_at' => null
                ]
            );
        });

        Ief::query()->each(function (Ief $ief): void {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $ief->code],
                [
                    'libelle' => $ief->libelle,
                    // 'type' => 'IEF', // ❌ À SUPPRIMER
                    // 'perimetre' => 'regional', // ❌ À SUPPRIMER
                    'ia_id' => $ief->ia_id,
                    'ief_id' => $ief->id,
                    'est_actif' => true,
                    'deleted_at' => null
                ]
            );
        });
    }
}