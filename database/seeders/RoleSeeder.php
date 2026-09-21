<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use App\Models\Admin\TypeRole;

class RoleSeeder extends Seeder
{

   
    public function run(): void
    {
        $roles = [
            [
                'nom' => 'Agent DECPC',
                'slug' => 'agent_decpc',
                'description' => 'Personnel et indemnités dans le périmètre DECPC attribué',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Agent DRH',
                'slug' => 'agent_drh',
                'description' => 'Consultation du personnel dans le périmètre attribué',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'type_role_code' => 'systeme',
                'est_actif' => true,
            ],
            [
                'nom' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Gestion de l\'application et des utilisateurs',
                'type_role_code' => 'admin',
                'est_actif' => true,
            ],
            // [
            //     'nom' => 'Paramétreur',
            //     'slug' => 'parametreur',
            //     'description' => 'Gestion des paramètres de l\'application',
            //     'type_role_code' => 'admin',
            //     'est_actif' => true,
            // ],
            [
                'nom' => 'Gestionnaire IA',
                'slug' => 'gestionnaire_ia',
                'description' => 'Gestion des IEF et enseignants de l\'IA',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire IEF',
                'slug' => 'gestionnaire_ief',
                'description' => 'Gestion des enseignants de l\'IEF',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Directeur des ressources humaines',
                'slug' => 'drh',
                'description' => 'Gestion des ressources humaines',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'DECPC',
                'slug' => 'decpc',
                'description' => 'Planification, organisation et supervision des examens, concours professionnels et certifications (CAP, BEP, BT, BTS et CPS), ainsi que délivrance des diplômes et attestations correspondants',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Paie',
                'slug' => 'gestionnaire_paie',
                'description' => 'Gestion de la paie des enseignants',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Gestionnaire Budget',
                'slug' => 'gestionnaire_budget',
                'description' => 'Gestion du budget et des engagements',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Consultant',
                'slug' => 'consultant',
                'description' => 'Consultation des données uniquement',
                'type_role_code' => 'consultation',
                'est_actif' => true,
            ],
            [
                'nom' => 'Enseignant',
                'slug' => 'enseignant',
                'description' => 'Enseignement et gestion des cours',
                'type_role_code' => 'consultation',
                'est_actif' => true,
            ],
            
        ];

        foreach ($roles as $role) {
            $role['type_role_id'] = TypeRole::where('code', $role['type_role_code'])->value('id');
            unset($role['type_role_code']);

            // Chercher par nom, si existe on met à jour
            $existing = Role::where('slug', $role['slug'])->first();
            if ($existing) {
                $existing->update($role);
            } else {
                Role::create($role);
            }
        }
    }
}
