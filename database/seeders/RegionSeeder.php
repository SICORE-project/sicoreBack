<?php

namespace Database\Seeders;

use App\Models\Parametrage\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'DK', 'libelle' => 'Dakar', 'chef_lieu' => 'Dakar'],
            ['code' => 'DB', 'libelle' => 'Diourbel', 'chef_lieu' => 'Diourbel'],
            ['code' => 'FK', 'libelle' => 'Fatick', 'chef_lieu' => 'Fatick'],
            ['code' => 'KA', 'libelle' => 'Kaffrine', 'chef_lieu' => 'Kaffrine'],
            ['code' => 'KL', 'libelle' => 'Kaolack', 'chef_lieu' => 'Kaolack'],
            ['code' => 'KE', 'libelle' => 'Kédougou', 'chef_lieu' => 'Kédougou'],
            ['code' => 'KD', 'libelle' => 'Kolda', 'chef_lieu' => 'Kolda'],
            ['code' => 'LG', 'libelle' => 'Louga', 'chef_lieu' => 'Louga'],
            ['code' => 'MT', 'libelle' => 'Matam', 'chef_lieu' => 'Matam'],
            ['code' => 'SL', 'libelle' => 'Saint-Louis', 'chef_lieu' => 'Saint-Louis'],
            ['code' => 'SE', 'libelle' => 'Sédhiou', 'chef_lieu' => 'Sédhiou'],
            ['code' => 'TC', 'libelle' => 'Tambacounda', 'chef_lieu' => 'Tambacounda'],
            ['code' => 'TH', 'libelle' => 'Thiès', 'chef_lieu' => 'Thiès'],
            ['code' => 'ZG', 'libelle' => 'Ziguinchor', 'chef_lieu' => 'Ziguinchor'],
        ];

        foreach ($regions as $region) {
            Region::query()->updateOrCreate(
                ['code' => $region['code']],
                $region + ['est_actif' => true],
            );
        }
    }
}