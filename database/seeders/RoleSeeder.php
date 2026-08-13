<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nom' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'niveau' => 'systeme',
                'est_actif' => true,
            ],
            [
                'nom' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Gestion de l\'application et des utilisateurs',
                'niveau' => 'admin',
                'est_actif' => true,
            ],
            // [
            //     'nom' => 'Paramétreur',
            //     'slug' => 'parametreur',
            //     'description' => 'Gestion des paramètres de l\'application',
            //     'niveau' => 'admin',
            //     'est_actif' => true,
            // ],
            [
                'nom' => 'Gestionnaire IA',
                'slug' => 'gestionnaire_ia',
                'description' => 'Gestion des IEF et enseignants de l\'IA',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire IEF',
                'slug' => 'gestionnaire_ief',
                'description' => 'Gestion des enseignants de l\'IEF',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'DRH',
                'slug' => 'drh',
                'description' => 'Gestion des ressources humaines',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Paie',
                'slug' => 'gestionnaire_paie',
                'description' => 'Gestion de la paie des enseignants',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Budget',
                'slug' => 'gestionnaire_budget',
                'description' => 'Gestion du budget et des engagements',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Consultant',
                'slug' => 'consultant',
                'description' => 'Consultation des données uniquement',
                'niveau' => 'consultation',
                'est_actif' => true,
            ],
            [
                'nom' => 'Enseignant',
                'slug' => 'enseignant',
                'description' => 'Enseignement et gestion des cours',
                'niveau' => 'consultation',
                'est_actif' => true,
            ],
        ];

        foreach ($roles as $role) {
            // Chercher par nom, si existe on met à jour
            $existing = Role::where('nom', $role['nom'])->first();
            if ($existing) {
                $existing->update($role);
            } else {
                Role::create($role);
            }
        }
    }
}
