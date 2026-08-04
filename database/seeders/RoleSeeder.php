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
                'libelle' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'niveau' => 'systeme',   // <-- systeme (existe déjà)
                'est_actif' => true,
            ],
            [
                'libelle' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Gestion de l\'application et des utilisateurs',
                'niveau' => 'admin',     // <-- AJOUTER admin à l'ENUM
                'est_actif' => true,
            ],
            [
                'libelle' => 'Paramétreur',
                'slug' => 'parametreur',
                'description' => 'Gestion des paramètres de l\'application',
                'niveau' => 'admin',     // <-- AJOUTER admin à l'ENUM
                'est_actif' => true,
            ],
            [
                'libelle' => 'Gestionnaire IA',
                'slug' => 'gestionnaire_ia',
                'description' => 'Gestion des IEF et enseignants de l\'IA',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'libelle' => 'Gestionnaire IEF',
                'slug' => 'gestionnaire_ief',
                'description' => 'Gestion des enseignants de l\'IEF',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'libelle' => 'DRH',
                'slug' => 'drh',
                'description' => 'Gestion des ressources humaines',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'libelle' => 'Gestionnaire Paie',
                'slug' => 'gestionnaire_paie',
                'description' => 'Gestion de la paie des enseignants',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'libelle' => 'Gestionnaire Budget',
                'slug' => 'gestionnaire_budget',
                'description' => 'Gestion du budget et des engagements',
                'niveau' => 'gestion',
                'est_actif' => true,
            ],
            [
                'libelle' => 'Consultant',
                'slug' => 'consultant',
                'description' => 'Consultation des données uniquement',
                'niveau' => 'consultation',
                'est_actif' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}