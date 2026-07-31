<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [

            [
                'nom' => 'Administrateur',
                'slug' => 'administrateur'
            ],

            [
                'nom' => 'Gestionnaire RH',
                'slug' => 'gestionnaire-rh'
            ],

            [
                'nom' => 'Gestionnaire Paie',
                'slug' => 'gestionnaire-paie'
            ],

            [
                'nom' => 'Chef de Service',
                'slug' => 'chef-service'
            ],

            [
                'nom' => 'Consultation',
                'slug' => 'consultation'
            ],

        ];

        foreach ($roles as $role) {

            Role::firstOrCreate(
                ['slug'=>$role['slug']],
                $role
            );

        }
    }
}