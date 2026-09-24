<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class RecruitmentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['read' => 'Consulter les recrutements', 'import' => 'Importer les recrutements', 'transmit' => 'Transmettre les recrutements', 'service' => 'Enregistrer la prise de service', 'transition' => 'Valider les changements de statut'] as $action => $label) {
            Permission::firstOrCreate(['slug' => 'recruitment.'.$action], [
                'nom' => $label, 'groupe' => 'personnel', 'module' => 'recruitment', 'action' => $action, 'est_actif' => true,
            ]);
        }
        foreach (Role::all() as $role) {
            $actions = match ($role->slug) {
                'super_admin','admin' => ['read', 'import', 'transmit', 'service', 'transition'],
                'drh','agent_drh' => ['read', 'import', 'transmit', 'transition'],
                'gestionnaire_ia','agent_ia' => ['read', 'service'],
                'dage','gestionnaire_paie','gestionnaire_budget' => ['read'],
                default => [],
            };
            $role->permissions()->syncWithoutDetaching(Permission::whereIn('slug', array_map(fn ($a) => 'recruitment.'.$a, $actions))->pluck('id')->all());
            if (in_array($role->slug, ['drh', 'agent_drh'], true)) {
                $role->permissions()->detach(Permission::whereIn('slug', ['enseignants.create', 'enseignants.update', 'enseignants.delete', 'enseignants.validate', 'enseignants.comptes_bancaires.manage'])->pluck('id')->all());
            }
        }
    }
}
