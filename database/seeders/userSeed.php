<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class userSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'prenom' => 'Admin',
                'nom' => 'Admin',
                'email' => 'admin@example.com',
                'login' => 'admin',
                'telephone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => 1,
                'lieu_service_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'statut' => 1,
                'created_by' => null,
            ],
            [
                'prenom' => 'Adèle',
                'nom' => 'faye',
                'email' => 'adele@example.com',
                'login' => 'adele',
                'telephone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => 1,
                'lieu_service_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'statut' => 1,
                'created_by' => null,
            ],
            [
                'prenom' => 'Amina',
                'nom' => 'Sagna',
                'email' => 'amina@example.com',
                'login' => 'amina',
                'telephone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => 1,
                'lieu_service_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'statut' => 1,
                'created_by' => null,
            ],
            [
                'prenom' => 'Mame Dieye',
                'nom' => 'dieng',
                'email' => 'mddieng@example.com',
                'login' => 'mddieng',
                'telephone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => 1,
                'lieu_service_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'statut' => 1,
                'created_by' => null,
            ],
            [
                'prenom' => 'Oulimata',
                'nom' => 'Cissé',
                'email' => 'oulimata@example.com',
                'login' => 'oulimata',
                'telephone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => 1,
                'lieu_service_id' => null,
                'ia_id' => null,
                'ief_id' => null,
                'statut' => 1,
                'created_by' => null,
            ]
        ];

        foreach ($users as $user) {
            \App\Models\User::create($user);
        }
    }
}
