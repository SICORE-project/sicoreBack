<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles
        $superAdminRole = Role::query()->where('slug', 'super_admin')->first();
        $adminRole = Role::query()->where('slug', 'admin')->first();
        $gestionnaireIaRole = Role::query()->where('slug', 'gestionnaire_ia')->first();

        // Si les rôles n'existent pas, les créer d'abord
        if (! $superAdminRole) {
            $this->call([
                TypeRoleSeeder::class,
                RoleSeeder::class,
                PermissionSeeder::class,
                RolePermissionSeeder::class,
            ]);

            $superAdminRole = Role::query()->where('slug', 'super_admin')->firstOrFail();
            $adminRole = Role::query()->where('slug', 'admin')->first();
            $gestionnaireIaRole = Role::query()->where('slug', 'gestionnaire_ia')->first();
        }

        // Récupérer les lieux de service
        $services = LieuService::query()
            ->actif()
            ->orderBy('code')
            ->get()
            ->keyBy(fn (LieuService $lieu) => strtoupper((string) $lieu->code));

        $dage = $services->get('DAGE');
        $ia = $services->first(fn (LieuService $lieu) => strtoupper((string) $lieu->type) === 'IA');

        // Définir les utilisateurs
        $users = [
            // === Super Administrateur ===
            [
                'nom' => 'Diop',
                'prenom' => 'Mamadou',
                'email' => 'mamadou.diop@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole?->id,
                'statut' => 'actif',
                'fonction' => 'Super Administrateur',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            [
                'nom' => 'ndiaye',
                'prenom' => 'Diockel',
                'email' => 'ndiayediockel1996@gmail.com',
                'password' => Hash::make('Dio.770123877'),
                'role_id' => $superAdminRole?->id,
                'statut' => 'actif',
                'fonction' => 'Super Administrateur',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Administrateur ===
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Aminata',
                'email' => 'aminata.ndiaye@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $adminRole?->id,
                'lieu_service_id' => $dage?->id,
                'statut' => 'actif',
                'fonction' => 'Administrateur',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Gestionnaire IA ===
            [
                'nom' => 'Fall',
                'prenom' => 'Fatou',
                'email' => 'fatou.fall@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $gestionnaireIaRole?->id,
                'lieu_service_id' => $ia?->id,
                'statut' => 'actif',
                'fonction' => 'Gestionnaire IA',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
        ];

        if ($gestionnaireIaRole && ! $ia) {
            $this->command?->warn('Aucun lieu de service de type IA : Fatou Fall reste sans rattachement.');
        }

        // Créer les utilisateurs
        foreach ($users as $userData) {
            User::query()->firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}