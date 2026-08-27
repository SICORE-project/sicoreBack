<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\PermissionGroupe;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionGroupeController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => PermissionGroupe::withCount('modules')->orderBy('libelle')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:permission_groupes,code'],
            'libelle' => ['required', 'string', 'max:100'],
            'est_actif' => ['sometimes', 'boolean'],
        ]);

        $groupe = PermissionGroupe::create($data);
        return response()->json(['success' => true, 'message' => 'Groupe créé avec succès.', 'data' => $groupe], 201);
    }

    public function update(Request $request, PermissionGroupe $groupe)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('permission_groupes', 'code')->ignore($groupe)],
            'libelle' => ['required', 'string', 'max:100'],
            'est_actif' => ['sometimes', 'boolean'],
        ]);

        $ancienCode = $groupe->code;
        $groupe->update($data);
        if ($ancienCode !== $groupe->code) {
            Permission::where('groupe', $ancienCode)->update(['groupe' => $groupe->code]);
        }

        return response()->json(['success' => true, 'message' => 'Groupe modifié avec succès.', 'data' => $groupe]);
    }

    public function destroy(PermissionGroupe $groupe)
    {
        if ($groupe->modules()->exists() || Permission::where('groupe', $groupe->code)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ce groupe est utilisé et ne peut pas être supprimé.'], 409);
        }
        $groupe->delete();
        return response()->json(['success' => true, 'message' => 'Groupe supprimé avec succès.']);
    }
}
