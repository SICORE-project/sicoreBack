<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\User;
use App\Models\Admin\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $parametreurRole = Role::where('slug', 'parametreur')->first();
        $gestionnaireIaRole = Role::where('slug', 'gestionnaire_ia')->first();
        $gestionnaireIefRole = Role::where('slug', 'gestionnaire_ief')->first();
        $drhRole = Role::where('slug', 'drh')->first();
        $paieRole = Role::where('slug', 'gestionnaire_paie')->first();
        $budgetRole = Role::where('slug', 'gestionnaire_budget')->first();
        $consultantRole = Role::where('slug', 'consultant')->first();

        $users = [
            // === Super Administrateur ===
            [
                'nom' => 'Diop',
                'prenom' => 'Mamadou',
                'email' => 'mamadou.diop@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole ? $superAdminRole->id : null,
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
                'role_id' => $adminRole ? $adminRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Administrateur',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Paramétreur ===
            [
                'nom' => 'Sow',
                'prenom' => 'Ibrahima',
                'email' => 'ibrahima.sow@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $parametreurRole ? $parametreurRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Paramétreur',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Gestionnaire IA ===
            [
                'nom' => 'Fall',
                'prenom' => 'Fatou',
                'email' => 'fatou.fall@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $gestionnaireIaRole ? $gestionnaireIaRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Gestionnaire IA',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Gestionnaire IEF ===
            [
                'nom' => 'Ba',
                'prenom' => 'Oumar',
                'email' => 'oumar.ba@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $gestionnaireIefRole ? $gestionnaireIefRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Gestionnaire IEF',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === DRH ===
            [
                'nom' => 'Dieng',
                'prenom' => 'Mame Dieye',
                'email' => 'mamedieye.dieng@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $drhRole ? $drhRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Directeur RH',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Gestionnaire Paie ===
            [
                'nom' => 'Sarr',
                'prenom' => 'Adèle',
                'email' => 'adele.sarr@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $paieRole ? $paieRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Gestionnaire Paie',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Gestionnaire Budget ===
            [
                'nom' => 'Cissé',
                'prenom' => 'Oulimata',
                'email' => 'oulimata.cisse@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $budgetRole ? $budgetRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Gestionnaire Budget',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Consultant ===
            [
                'nom' => 'Gueye',
                'prenom' => 'Pape',
                'email' => 'pape.gueye@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $consultantRole ? $consultantRole->id : null,
                'statut' => 'actif',
                'fonction' => 'Consultant',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}