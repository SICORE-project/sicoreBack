<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\TypeRole;
use Illuminate\Database\Seeder;

class AgentDrhSeeder extends Seeder
{
    public function run(): void
    {
        $type = TypeRole::firstOrCreate(['code' => 'gestion'], ['libelle' => 'Gestion', 'est_actif' => true]);
        $role = Role::firstOrCreate(['slug' => 'agent_drh'], [
            'nom' => 'Agent DRH', 'type_role_id' => $type->id, 'est_actif' => true,
            'description' => 'Gestion du personnel dans le périmètre attribué',
        ]);
        foreach (['read' => 'Consulter', 'create' => 'Créer', 'update' => 'Modifier'] as $action => $label) {
            $permission = Permission::firstOrCreate(['slug' => 'enseignants.'.$action], [
                'nom' => $label.' les enseignants', 'groupe' => 'personnel',
                'module' => 'enseignants', 'action' => $action, 'est_actif' => true,
            ]);
            if ($action === 'read') {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
        $this->call(RecruitmentPermissionSeeder::class);
    }
}
