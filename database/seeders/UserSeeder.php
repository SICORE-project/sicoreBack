<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\User;
use App\Models\Admin\Role;
use App\Models\Parametrage\LieuService;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $gestionnaireIaRole = Role::where('slug', 'gestionnaire_ia')->first();
        $gestionnaireIefRole = Role::where('slug', 'gestionnaire_ief')->first();
        $drhRole = Role::where('slug', 'drh')->first();
        $paieRole = Role::where('slug', 'gestionnaire_paie')->first();
        $budgetRole = Role::where('slug', 'gestionnaire_budget')->first();
        $consultantRole = Role::where('slug', 'consultant')->first();
        $enseignantRole = Role::where('slug', 'enseignant')->first();

        $services = LieuService::query()
            ->actif()
            ->orderBy('code')
            ->get()
            ->keyBy(fn (LieuService $lieu) => strtoupper((string) $lieu->code));

        $dage = $services->get('DAGE');
        $drh = $services->get('DRH');
        $decpc = $services->get('DECPC');
        $ia = $services->first(fn (LieuService $lieu) => strtoupper((string) $lieu->type) === 'IA');
        $ief = $services->first(fn (LieuService $lieu) => strtoupper((string) $lieu->type) === 'IEF'
            && ($ia === null || (int) $lieu->ia_id === (int) $ia->ia_id));
        $ief ??= $services->first(fn (LieuService $lieu) => strtoupper((string) $lieu->type) === 'IEF');

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
                'lieu_service_id' => $dage?->id,
                'statut' => 'actif',
                'fonction' => 'Administrateur',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Paramétreur ===
            // [
            //     'nom' => 'Sow',
            //     'prenom' => 'Ibrahima',
            //     'email' => 'ibrahima.sow@sicore.sn',
            //     'password' => Hash::make('password'),
            //     'role_id' => $parametreurRole ? $parametreurRole->id : null,
            //     'statut' => 'actif',
            //     'fonction' => 'Paramétreur',
            //     'genre' => 'masculin',
            //     'must_change_password' => false,
            //     'tentatives_connexion' => 0,
            // ],
            // === Gestionnaire IA ===
            [
                'nom' => 'Fall',
                'prenom' => 'Fatou',
                'email' => 'fatou.fall@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $gestionnaireIaRole ? $gestionnaireIaRole->id : null,
                'lieu_service_id' => $ia?->id,
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
                'lieu_service_id' => $ief?->id,
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
                'lieu_service_id' => $drh?->id,
                'statut' => 'actif',
                'fonction' => 'Directeur RH',
                'genre' => 'feminin',
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
                'lieu_service_id' => $dage?->id,
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
                'lieu_service_id' => $dage?->id,
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
                'lieu_service_id' => $decpc?->id,
                'statut' => 'actif',
                'fonction' => 'Consultant',
                'genre' => 'masculin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
            // === Enseignant ===
            [
                'nom' => 'Diouf',
                'prenom' => 'Aissatou',
                'email' => 'aissatou.diouf@sicore.sn',
                'password' => Hash::make('password'),
                'role_id' => $enseignantRole ? $enseignantRole->id : null,
                'lieu_service_id' => $ief?->id,
                'statut' => 'actif',
                'fonction' => 'Enseignant',
                'genre' => 'feminin',
                'must_change_password' => false,
                'tentatives_connexion' => 0,
            ],
        ];

        if ($gestionnaireIaRole && ! $ia) {
            $this->command?->warn('Aucun lieu de service de type IA : Fatou Fall reste sans rattachement.');
        }
        if (($gestionnaireIefRole || $enseignantRole) && ! $ief) {
            $this->command?->warn('Aucun lieu de service de type IEF : les comptes IEF restent sans rattachement.');
        }

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}