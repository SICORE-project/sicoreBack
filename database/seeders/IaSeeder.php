<?php

namespace Database\Seeders;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Region;
use Illuminate\Database\Seeder;
use RuntimeException;

class IaSeeder extends Seeder
{
    public function run(): void
    {
        $ias = [
            ['code' => 'IA-DKR', 'libelle' => 'Inspection d’Académie de Dakar', 'region_code' => 'DK'],
            ['code' => 'IA-DBL', 'libelle' => 'Inspection d’Académie de Diourbel', 'region_code' => 'DB'],
            ['code' => 'IA-FTK', 'libelle' => 'Inspection d’Académie de Fatick', 'region_code' => 'FK'],
            ['code' => 'IA-KFR', 'libelle' => 'Inspection d’Académie de Kaffrine', 'region_code' => 'KA'],
            ['code' => 'IA-KLK', 'libelle' => 'Inspection d’Académie de Kaolack', 'region_code' => 'KL'],
            ['code' => 'IA-KDG', 'libelle' => 'Inspection d’Académie de Kédougou', 'region_code' => 'KE'],
            ['code' => 'IA-KLD', 'libelle' => 'Inspection d’Académie de Kolda', 'region_code' => 'KD'],
            ['code' => 'IA-LGA', 'libelle' => 'Inspection d’Académie de Louga', 'region_code' => 'LG'],
            ['code' => 'IA-MTM', 'libelle' => 'Inspection d’Académie de Matam', 'region_code' => 'MT'],
            ['code' => 'IA-SLS', 'libelle' => 'Inspection d’Académie de Saint-Louis', 'region_code' => 'SL'],
            ['code' => 'IA-SDH', 'libelle' => 'Inspection d’Académie de Sédhiou', 'region_code' => 'SE'],
            ['code' => 'IA-TBA', 'libelle' => 'Inspection d’Académie de Tambacounda', 'region_code' => 'TC'],
            ['code' => 'IA-THS', 'libelle' => 'Inspection d’Académie de Thiès', 'region_code' => 'TH'],
            ['code' => 'IA-ZGN', 'libelle' => 'Inspection d’Académie de Ziguinchor', 'region_code' => 'ZG'],
        ];

        foreach ($ias as $data) {
            $regionId = Region::query()->where('code', $data['region_code'])->value('id');

            if ($regionId === null) {
                throw new RuntimeException("Région introuvable pour le code {$data['region_code']}.");
            }

            $ia = Ia::withTrashed()->updateOrCreate(
                ['code' => $data['code']],
                ['libelle' => $data['libelle'], 'region_id' => $regionId],
            );

            if ($ia->trashed()) {
                $ia->restore();
            }
        }
    }
}
