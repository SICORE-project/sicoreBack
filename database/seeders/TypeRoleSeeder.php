<?php

namespace Database\Seeders;

use App\Models\Admin\TypeRole;
use Illuminate\Database\Seeder;

class TypeRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'systeme', 'libelle' => 'Système'],
            ['code' => 'admin', 'libelle' => 'Administration'],
            ['code' => 'gestion', 'libelle' => 'Gestion'],
            ['code' => 'consultation', 'libelle' => 'Consultation'],
        ] as $typeRole) {
            TypeRole::updateOrCreate(['code' => $typeRole['code']], $typeRole + ['est_actif' => true]);
        }
    }
}
