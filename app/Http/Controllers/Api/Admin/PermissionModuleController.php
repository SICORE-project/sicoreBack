<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\PermissionModule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionModuleController extends Controller
{
    public function index(Request $request)
    {
        $query = PermissionModule::with([
                'groupe',
                'permissions:id,nom,module,est_actif',
            ])
            ->withCount([
                'permissions',
                'permissions as permissions_actives_count' => fn ($query) => $query->where('est_actif', true),
            ])
            ->orderBy('libelle');
        if ($request->filled('groupe')) {
            $query->whereHas('groupe', fn ($q) => $q->where('code', $request->groupe));
        }
        $modules = $query->get()->each(function (PermissionModule $module) {
            $module->setAttribute('permissions_noms', $module->permissions->pluck('nom')->values());
            $module->setAttribute('permissions_actives', $module->permissions->where('est_actif', true)->pluck('nom')->values());
            $module->setAttribute('statut_utilisation', $module->permissions_count > 0 ? 'Utilisé' : 'Non utilisé');
        });

        return response()->json(['success' => true, 'data' => $modules]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $module = PermissionModule::create($data);
        return response()->json(['success' => true, 'message' => 'Module créé avec succès.', 'data' => $module->load('groupe')], 201);
    }

    public function update(Request $request, PermissionModule $module)
    {
        $data = $this->validateData($request, $module);
        $ancienCode = $module->code;
        $module->update($data);

        if ($ancienCode !== $module->code) {
            Permission::where('module', $ancienCode)->update(['module' => $module->code]);
        }

        return response()->json(['success' => true, 'message' => 'Module modifié avec succès.', 'data' => $module->load('groupe')]);
    }

    public function destroy(PermissionModule $module)
    {
        if (Permission::where('module', $module->code)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ce module est utilisé et ne peut pas être supprimé.'], 409);
        }
        $module->delete();
        return response()->json(['success' => true, 'message' => 'Module supprimé avec succès.']);
    }

    private function validateData(Request $request, ?PermissionModule $module = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('permission_modules', 'code')->ignore($module)],
            'libelle' => ['required', 'string', 'max:100'],
            'groupe_id' => ['nullable', 'integer', 'exists:permission_groupes,id'],
            'est_actif' => ['sometimes', 'boolean'],
        ]);
    }
}
