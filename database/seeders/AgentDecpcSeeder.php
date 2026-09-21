<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\TypeRole;
use Illuminate\Database\Seeder;

class AgentDecpcSeeder extends Seeder
{
    public function run(): void
    {
        $type = TypeRole::firstOrCreate(['code' => 'gestion'], ['libelle' => 'Gestion', 'est_actif' => true]);
        $role = Role::firstOrCreate(['slug' => 'agent_decpc'], [
            'nom' => 'Agent DECPC', 'type_role_id' => $type->id, 'est_actif' => true,
            'description' => 'Personnel et indemnités dans le périmètre DECPC attribué',
        ]);
        foreach (['enseignants' => 'personnel', 'indemnites' => 'indemnites'] as $module => $group) {
            foreach (['read', 'create', 'update', 'delete', 'validate', 'manage'] as $action) {
                $permission = Permission::firstOrCreate(['slug' => "$module.$action"], [
                    'nom' => "$module : $action", 'groupe' => $group, 'module' => $module,
                    'action' => $action, 'est_actif' => true,
                ]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
        $permissions = Permission::where('slug', 'like', 'enseignants.%')
            ->orWhere('slug', 'like', 'indemnites.%')->pluck('id')->all();
        $role->permissions()->syncWithoutDetaching($permissions);
        $decpc = Role::where('slug', 'decpc')->first();
        if ($decpc) {
            $decpc->permissions()->syncWithoutDetaching($permissions);
            $role->permissions()->syncWithoutDetaching($decpc->permissions()->pluck('permissions.id')->all());
        }
    }
}
