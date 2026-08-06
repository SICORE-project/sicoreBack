<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $admin = Role::where('slug', 'admin')->first();
        $parametreur = Role::where('slug', 'parametreur')->first();
        $gestionnaireIa = Role::where('slug', 'gestionnaire_ia')->first();
        $gestionnaireIef = Role::where('slug', 'gestionnaire_ief')->first();
        $drh = Role::where('slug', 'drh')->first();
        $gestionnairePaie = Role::where('slug', 'gestionnaire_paie')->first();
        $gestionnaireBudget = Role::where('slug', 'gestionnaire_budget')->first();
        $consultant = Role::where('slug', 'consultant')->first();

        // Récupérer toutes les permissions
        $allPermissions = Permission::pluck('id')->toArray();

        // === SUPER ADMIN : toutes les permissions ===
        if ($superAdmin) {
            $superAdmin->permissions()->sync($allPermissions);
        }

        // === ADMIN ===
        if ($admin) {
            $adminPermissions = Permission::whereIn('groupe', ['administration', 'parametrage', 'personnel'])->pluck('id')->toArray();
            $admin->permissions()->sync($adminPermissions);
        }

        // === PARAMÉTREUR ===
        if ($parametreur) {
            $paramPermissions = Permission::where('groupe', 'parametrage')->pluck('id')->toArray();
            $parametreur->permissions()->sync($paramPermissions);
        }

        // === GESTIONNAIRE IA ===
        if ($gestionnaireIa) {
            $iaPermissions = Permission::whereIn('slug', [
                'parametrage.ia.read',
                'parametrage.ia.manage',
                'parametrage.ief.read',
                'parametrage.ief.manage',
                'enseignants.read',
                'enseignants.create',
                'enseignants.update',
                'enseignants.validate',
            ])->pluck('id')->toArray();
            $gestionnaireIa->permissions()->sync($iaPermissions);
        }

        // === GESTIONNAIRE IEF ===
        if ($gestionnaireIef) {
            $iefPermissions = Permission::whereIn('slug', [
                'parametrage.ief.read',
                'enseignants.read',
                'enseignants.create',
                'enseignants.update',
            ])->pluck('id')->toArray();
            $gestionnaireIef->permissions()->sync($iefPermissions);
        }

        // === DRH ===
        if ($drh) {
            $drhPermissions = Permission::whereIn('slug', [
                'enseignants.read',
                'enseignants.create',
                'enseignants.update',
                'enseignants.delete',
                'enseignants.validate',
                'enseignants.export',
                'enseignants.search',
            ])->pluck('id')->toArray();
            $drh->permissions()->sync($drhPermissions);
        }

        // === GESTIONNAIRE PAIE ===
        if ($gestionnairePaie) {
            $paiePermissions = Permission::where('groupe', 'paie')->pluck('id')->toArray();
            $gestionnairePaie->permissions()->sync($paiePermissions);
        }

        // === GESTIONNAIRE BUDGET ===
        if ($gestionnaireBudget) {
            $budgetPermissions = Permission::where('groupe', 'budget')->pluck('id')->toArray();
            $gestionnaireBudget->permissions()->sync($budgetPermissions);
        }

        // === CONSULTANT ===
        if ($consultant) {
            $consultPermissions = Permission::where('action', 'read')->pluck('id')->toArray();
            $consultant->permissions()->sync($consultPermissions);
        }
    }
}
