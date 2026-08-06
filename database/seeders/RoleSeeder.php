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
                'niveau' => 'admin_metier',
                'est_actif' => true,
            ],
            [
                'nom' => 'Paramétreur',
                'slug' => 'parametreur',
                'description' => 'Gestion des paramètres de l\'application',
                'niveau' => 'admin_metier',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire IA',
                'slug' => 'gestionnaire_ia',
                'description' => 'Gestion des IEF et enseignants de l\'IA',
                'niveau' => 'gestionnaire_ia',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire IEF',
                'slug' => 'gestionnaire_ief',
                'description' => 'Gestion des enseignants de l\'IEF',
                'niveau' => 'gestionnaire_ief',
                'est_actif' => true,
            ],
            [
                'nom' => 'DRH',
                'slug' => 'drh',
                'description' => 'Gestion des ressources humaines',
                'niveau' => 'gestionnaire_rh',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Paie',
                'slug' => 'gestionnaire_paie',
                'description' => 'Gestion de la paie des enseignants',
                'niveau' => 'gestionnaire_paie',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Budget',
                'slug' => 'gestionnaire_budget',
                'description' => 'Gestion du budget et des engagements',
                'niveau' => 'gestionnaire_budget',
                'est_actif' => true,
            ],
            [
                'nom' => 'Consultant',
                'slug' => 'consultant',
                'description' => 'Consultation des données uniquement',
                'niveau' => 'consultation',
                'est_actif' => true,
            ],
        ];

        foreach ($roles as $role) {
            // Chercher par slug, si existe on met à jour
            $existing = Role::where('slug', $role['slug'])->first();
            if ($existing) {
                $existing->update($role);
            } else {
                Role::create($role);
            }
        }
    }
}
