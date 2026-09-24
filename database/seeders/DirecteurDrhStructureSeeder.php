<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirecteurDrhStructureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::where('email', 'mamedieye.dieng@sicore.sn')->firstOrFail();
            $role = Role::where('slug', 'drh')->firstOrFail();
            $structure = LieuService::withTrashed()->firstOrNew(['code' => 'DRH']);
            $structure->fill([
                'libelle' => 'Direction des ressources humaines',
                'type' => 'DRH', 'perimetre' => 'national', 'est_actif' => true,
                'ia_id' => null, 'ief_id' => null,
            ]);
            $structure->deleted_at = null;
            $structure->save();
            $role->update(['nom' => 'Directeur des ressources humaines']);
            $user->update([
                'role_id' => $role->id,
                'fonction' => 'Directeur des ressources humaines',
                'lieu_service_id' => $structure->id,
                'ia_id' => null, 'ief_id' => null,
            ]);
        });
    }
}
