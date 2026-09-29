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
                'nom' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'type_role_code' => 'systeme',
                'est_actif' => true,
            ],
            [
                'nom' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'DAGE',
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
                'nom' => 'DECPC',
                'slug' => 'decpc',
                'description' => 'Choix des participants aux examens et envoi de la liste de convocation à la DAGE',
                'type_role_code' => 'gestion',
                'est_actif' => true,
            ],
            [
                'nom' => 'Enseignant',
                'slug' => 'enseignant',
                'description' => 'Consultation de son propre dossier et de ses bulletins',
                'type_role_code' => 'consultation',
                'est_actif' => true,
            ],
        ];

        foreach ($roles as $role) {
            $role['type_role_id'] = TypeRole::where('code', $role['type_role_code'])->value('id');
            unset($role['type_role_code']);

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
