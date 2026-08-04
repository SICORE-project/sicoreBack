<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // === ADMINISTRATION ===
            [
                'libelle' => 'Consulter les utilisateurs',  // <-- libelle au lieu de nom
                'slug' => 'administration.users.read',
                'structure' => 'administration',
                'module' => 'users',
                'action' => 'read',
                'description' => 'Consulter les utilisateurs',
            ],
            [
                'libelle' => 'Créer un utilisateur',
                'slug' => 'administration.users.create',
                'structure' => 'administration',
                'module' => 'users',
                'action' => 'create',
                'description' => 'Créer un utilisateur',
            ],
            [
                'libelle' => 'Modifier un utilisateur',
                'slug' => 'administration.users.update',
                'structure' => 'administration',
                'module' => 'users',
                'action' => 'update',
                'description' => 'Modifier un utilisateur',
            ],
            [
                'libelle' => 'Supprimer un utilisateur',
                'slug' => 'administration.users.delete',
                'structure' => 'administration',
                'module' => 'users',
                'action' => 'delete',
                'description' => 'Supprimer un utilisateur',
            ],
            [
                'libelle' => 'Consulter les rôles',
                'slug' => 'administration.roles.read',
                'structure' => 'administration',
                'module' => 'roles',
                'action' => 'read',
                'description' => 'Consulter les rôles',
            ],
            [
                'libelle' => 'Gérer les rôles',
                'slug' => 'administration.roles.manage',
                'structure' => 'administration',
                'module' => 'roles',
                'action' => 'manage',
                'description' => 'Gérer les rôles',
            ],
            [
                'libelle' => 'Consulter les permissions',
                'slug' => 'administration.permissions.read',
                'structure' => 'administration',
                'module' => 'permissions',
                'action' => 'read',
                'description' => 'Consulter les permissions',
            ],
            [
                'libelle' => 'Gérer les permissions',
                'slug' => 'administration.permissions.manage',
                'structure' => 'administration',
                'module' => 'permissions',
                'action' => 'manage',
                'description' => 'Gérer les permissions',
            ],

            // === PARAMÉTRAGE ===
            [
                'libelle' => 'Consulter les IA',
                'slug' => 'parametrage.ia.read',
                'structure' => 'parametrage',
                'module' => 'ia',
                'action' => 'read',
                'description' => 'Consulter les IA',
            ],
            [
                'libelle' => 'Gérer les IA',
                'slug' => 'parametrage.ia.manage',
                'structure' => 'parametrage',
                'module' => 'ia',
                'action' => 'manage',
                'description' => 'Gérer les IA',
            ],
            [
                'libelle' => 'Consulter les IEF',
                'slug' => 'parametrage.ief.read',
                'structure' => 'parametrage',
                'module' => 'ief',
                'action' => 'read',
                'description' => 'Consulter les IEF',
            ],
            [
                'libelle' => 'Gérer les IEF',
                'slug' => 'parametrage.ief.manage',
                'structure' => 'parametrage',
                'module' => 'ief',
                'action' => 'manage',
                'description' => 'Gérer les IEF',
            ],

            // === ENSEIGNANTS ===
            [
                'libelle' => 'Consulter les enseignants',
                'slug' => 'enseignants.read',
                'structure' => 'enseignants',
                'module' => 'enseignants',
                'action' => 'read',
                'description' => 'Consulter les enseignants',
            ],
            [
                'libelle' => 'Créer un enseignant',
                'slug' => 'enseignants.create',
                'structure' => 'enseignants',
                'module' => 'enseignants',
                'action' => 'create',
                'description' => 'Créer un enseignant',
            ],
            [
                'libelle' => 'Modifier un enseignant',
                'slug' => 'enseignants.update',
                'structure' => 'enseignants',
                'module' => 'enseignants',
                'action' => 'update',
                'description' => 'Modifier un enseignant',
            ],
            [
                'libelle' => 'Supprimer un enseignant',
                'slug' => 'enseignants.delete',
                'structure' => 'enseignants',
                'module' => 'enseignants',
                'action' => 'delete',
                'description' => 'Supprimer un enseignant',
            ],
            [
                'libelle' => 'Valider un enseignant',
                'slug' => 'enseignants.validate',
                'structure' => 'enseignants',
                'module' => 'enseignants',
                'action' => 'validate',
                'description' => 'Valider un enseignant',
            ],

            // === PAIE ===
            [
                'libelle' => 'Consulter les bulletins de paie',
                'slug' => 'paie.bulletins.read',
                'structure' => 'paie',
                'module' => 'bulletins',
                'action' => 'read',
                'description' => 'Consulter les bulletins de paie',
            ],
            [
                'libelle' => 'Générer les bulletins de paie',
                'slug' => 'paie.bulletins.generate',
                'structure' => 'paie',
                'module' => 'bulletins',
                'action' => 'generate',
                'description' => 'Générer les bulletins de paie',
            ],
            [
                'libelle' => 'Valider les bulletins de paie',
                'slug' => 'paie.bulletins.validate',
                'structure' => 'paie',
                'module' => 'bulletins',
                'action' => 'validate',
                'description' => 'Valider les bulletins de paie',
            ],

            // === INDEMNITÉS ===
            [
                'libelle' => 'Consulter les indemnités',
                'slug' => 'indemnites.read',
                'structure' => 'indemnites',
                'module' => 'indemnites',
                'action' => 'read',
                'description' => 'Consulter les indemnités',
            ],
            [
                'libelle' => 'Gérer les indemnités',
                'slug' => 'indemnites.manage',
                'structure' => 'indemnites',
                'module' => 'indemnites',
                'action' => 'manage',
                'description' => 'Gérer les indemnités',
            ],
            [
                'libelle' => 'Valider les indemnités',
                'slug' => 'indemnites.validate',
                'structure' => 'indemnites',
                'module' => 'indemnites',
                'action' => 'validate',
                'description' => 'Valider les indemnités',
            ],

            // === BUDGET ===
            [
                'libelle' => 'Consulter le budget',
                'slug' => 'budget.read',
                'structure' => 'budget',
                'module' => 'budget',
                'action' => 'read',
                'description' => 'Consulter le budget',
            ],
            [
                'libelle' => 'Gérer le budget',
                'slug' => 'budget.manage',
                'structure' => 'budget',
                'module' => 'budget',
                'action' => 'manage',
                'description' => 'Gérer le budget',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}