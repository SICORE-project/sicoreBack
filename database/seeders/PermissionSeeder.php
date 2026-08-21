<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // === ADMINISTRATION ===
            ['nom' => 'Consulter les utilisateurs', 'slug' => 'administration.users.read', 'groupe' => 'administration', 'module' => 'users', 'action' => 'read'],
            ['nom' => 'Créer un utilisateur', 'slug' => 'administration.users.create', 'groupe' => 'administration', 'module' => 'users', 'action' => 'create'],
            ['nom' => 'Modifier un utilisateur', 'slug' => 'administration.users.update', 'groupe' => 'administration', 'module' => 'users', 'action' => 'update'],
            ['nom' => 'Supprimer un utilisateur', 'slug' => 'administration.users.delete', 'groupe' => 'administration', 'module' => 'users', 'action' => 'delete'],
            ['nom' => 'Consulter les rôles', 'slug' => 'administration.roles.read', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'read'],
            ['nom' => 'Gérer les rôles', 'slug' => 'administration.roles.manage', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'manage'],
            ['nom' => 'Consulter les permissions', 'slug' => 'administration.permissions.read', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'read'],
            ['nom' => 'Gérer les permissions', 'slug' => 'administration.permissions.manage', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'manage'],
            ['nom' => 'Accès Gestionnaire IA','slug' => 'gestionnaire.ia.access','groupe' => 'administration','module' => 'gestionnaires','action' =>'access'],



            // === PARAMÉTRAGE ===
            ['nom' => 'Consulter les IA', 'slug' => 'parametrage.ia.read', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'read'],
            ['nom' => 'Gérer les IA', 'slug' => 'parametrage.ia.manage', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'manage'],
            ['nom' => 'Consulter les IEF', 'slug' => 'parametrage.ief.read', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'read'],
            ['nom' => 'Gérer les IEF', 'slug' => 'parametrage.ief.manage', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'manage'],
            ['nom' => 'Consulter les lieux de service', 'slug' => 'parametrage.lieux_service.read', 'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'read'],
            ['nom' => 'Gérer les lieux de service', 'slug' => 'parametrage.lieux_service.manage', 'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'manage'],
            ['nom' => 'Consulter les centres de formation', 'slug' => 'parametrage.centres_formation.read', 'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'read'],
            ['nom' => 'Gérer les centres de formation', 'slug' => 'parametrage.centres_formation.manage', 'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'manage'],
            ['nom' => 'Consulter les corps', 'slug' => 'parametrage.corps.read', 'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'read'],
            ['nom' => 'Gérer les corps', 'slug' => 'parametrage.corps.manage', 'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'manage'],
            ['nom' => 'Consulter les catégories', 'slug' => 'parametrage.categories.read', 'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'read'],
            ['nom' => 'Gérer les catégories', 'slug' => 'parametrage.categories.manage', 'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'manage'],
            ['nom' => 'Consulter les diplômes', 'slug' => 'parametrage.diplomes.read', 'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'read'],
            ['nom' => 'Gérer les diplômes', 'slug' => 'parametrage.diplomes.manage', 'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'manage'],
            ['nom' => 'Consulter les disciplines', 'slug' => 'parametrage.disciplines.read', 'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'read'],
            ['nom' => 'Gérer les disciplines', 'slug' => 'parametrage.disciplines.manage', 'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'manage'],
            ['nom' => 'Consulter les syndicats', 'slug' => 'parametrage.syndicats.read', 'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'read'],
            ['nom' => 'Gérer les syndicats', 'slug' => 'parametrage.syndicats.manage', 'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'manage'],
            ['nom' => 'Consulter les institutions financières', 'slug' => 'parametrage.institutions_financieres.read', 'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'read'],
            ['nom' => 'Gérer les institutions financières', 'slug' => 'parametrage.institutions_financieres.manage', 'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'manage'],

            // === ENSEIGNANTS ===
            ['nom' => 'Consulter les enseignants', 'slug' => 'enseignants.read', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'read'],
            ['nom' => 'Créer un enseignant', 'slug' => 'enseignants.create', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'create'],
            ['nom' => 'Modifier un enseignant', 'slug' => 'enseignants.update', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'update'],
            ['nom' => 'Supprimer un enseignant', 'slug' => 'enseignants.delete', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'delete'],
            ['nom' => 'Valider un enseignant', 'slug' => 'enseignants.validate', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'validate'],
            ['nom' => 'Rechercher un enseignant', 'slug' => 'enseignants.search', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'search'],
            ['nom' => 'Exporter les enseignants', 'slug' => 'enseignants.export', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'export'],
            ['nom' => 'Gérer les comptes bancaires des enseignants', 'slug' => 'enseignants.comptes_bancaires.manage', 'groupe' => 'personnel', 'module' => 'comptes_bancaires', 'action' => 'manage'],

            // === PAIE ===
            ['nom' => 'Consulter les bulletins de paie', 'slug' => 'paie.bulletins.read', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'read'],
            ['nom' => 'Générer les bulletins de paie', 'slug' => 'paie.bulletins.generate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'generate'],
            ['nom' => 'Valider les bulletins de paie', 'slug' => 'paie.bulletins.validate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'validate'],
            ['nom' => 'Consulter les rubriques de paie', 'slug' => 'paie.rubriques.read', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'read'],
            ['nom' => 'Gérer les rubriques de paie', 'slug' => 'paie.rubriques.manage', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'manage'],

            // === INDEMNITÉS ===
            ['nom' => 'Consulter les indemnités', 'slug' => 'indemnites.read', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'read'],
            ['nom' => 'Gérer les indemnités', 'slug' => 'indemnites.manage', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'manage'],
            ['nom' => 'Valider les indemnités', 'slug' => 'indemnites.validate', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'validate'],

            // === BUDGET ===
            ['nom' => 'Consulter le budget', 'slug' => 'budget.read', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'read'],
            ['nom' => 'Gérer le budget', 'slug' => 'budget.manage', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
