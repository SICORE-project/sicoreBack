<?php

namespace Database\Seeders;

use App\Models\Parametrage\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'DK', 'nom' => 'Dakar', 'chef_lieu' => 'Dakar'],
            ['code' => 'DB', 'nom' => 'Diourbel', 'chef_lieu' => 'Diourbel'],
            ['code' => 'FK', 'nom' => 'Fatick', 'chef_lieu' => 'Fatick'],
            ['code' => 'KA', 'nom' => 'Kaffrine', 'chef_lieu' => 'Kaffrine'],
            ['code' => 'KL', 'nom' => 'Kaolack', 'chef_lieu' => 'Kaolack'],
            ['code' => 'KE', 'nom' => 'Kédougou', 'chef_lieu' => 'Kédougou'],
            ['code' => 'KD', 'nom' => 'Kolda', 'chef_lieu' => 'Kolda'],
            ['code' => 'LG', 'nom' => 'Louga', 'chef_lieu' => 'Louga'],
            ['code' => 'MT', 'nom' => 'Matam', 'chef_lieu' => 'Matam'],
            ['code' => 'SL', 'nom' => 'Saint-Louis', 'chef_lieu' => 'Saint-Louis'],
            ['code' => 'SE', 'nom' => 'Sédhiou', 'chef_lieu' => 'Sédhiou'],
            ['code' => 'TC', 'nom' => 'Tambacounda', 'chef_lieu' => 'Tambacounda'],
            ['code' => 'TH', 'nom' => 'Thiès', 'chef_lieu' => 'Thiès'],
            ['code' => 'ZG', 'nom' => 'Ziguinchor', 'chef_lieu' => 'Ziguinchor'],
        ];

        foreach ($regions as $region) {
            Region::query()->updateOrCreate(
                ['code' => $region['code']],
                $region + ['est_actif' => true],
            );
        }
    }
}
