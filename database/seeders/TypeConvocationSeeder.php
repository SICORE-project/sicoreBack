<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeConvocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('types_convocation')->insert([
            [
                'code' => 'CORRECTION',
                'libelle' => 'Correction',
                'description' => 'Convocation pour une activité de correction.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SURVEILLANCE',
                'libelle' => 'Surveillance',
                'description' => 'Convocation pour une activité de surveillance.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'JURY',
                'libelle' => 'Jury',
                'description' => 'Convocation pour une activité de jury.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}