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
        foreach (['DRH', 'DAGE', 'DECPC'] as $code) {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $code],
                ['libelle' => $code, 'type' => $code, 'perimetre' => 'national', 'est_actif' => true, 'deleted_at' => null]
            );
        }

        Ia::query()->each(function (Ia $ia): void {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $ia->code],
                ['libelle' => $ia->libelle, 'type' => 'IA', 'perimetre' => 'regional', 'ia_id' => $ia->id, 'ief_id' => null, 'est_actif' => true, 'deleted_at' => null]
            );
        });

        Ief::query()->each(function (Ief $ief): void {
            LieuService::withTrashed()->updateOrCreate(
                ['code' => $ief->code],
                ['libelle' => $ief->libelle, 'type' => 'IEF', 'perimetre' => 'regional', 'ia_id' => $ief->ia_id, 'ief_id' => $ief->id, 'est_actif' => true, 'deleted_at' => null]
            );
        });
    }
}