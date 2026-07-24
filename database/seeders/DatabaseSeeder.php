<?php

namespace Database\Seeders;

use App\Models\roles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $role = roles::firstOrCreate(['libelle' => 'Administrateur']);

        User::firstOrCreate(
            ['email' => 'adminsicore@yopmail.com'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'password' => 'password',
                'role_id' => $role->id,
            ]
        );
    }
}
