<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\TypeRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TypeRoleController extends Controller
{
    public function index(Request $request)
    {
        $query = TypeRole::query()
            ->with(['roles:id,type_role_id,nom'])
            ->withCount('roles');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        if ($request->has('est_actif')) {
            $query->where('est_actif', $request->boolean('est_actif'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('libelle')->paginate($request->integer('per_page', 15)),
        ]);
    }

    public function all()
    {
        return response()->json([
            'success' => true,
            'data' => TypeRole::where('est_actif', true)
                ->with(['roles:id,type_role_id,nom'])
                ->withCount('roles')
                ->orderBy('libelle')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $typeRole = TypeRole::create($data + ['est_actif' => $data['est_actif'] ?? true]);

        return response()->json([
            'success' => true,
            'message' => 'Type de rôle créé avec succès.',
            'data' => $typeRole,
        ], 201);
    }

    public function show(TypeRole $typeRole)
    {
        return response()->json([
            'success' => true,
            'data' => $typeRole->load(['roles:id,type_role_id,nom'])->loadCount('roles'),
        ]);
    }

    public function update(Request $request, TypeRole $typeRole)
    {
        $typeRole->update($this->validated($request, $typeRole));

        return response()->json([
            'success' => true,
            'message' => 'Type de rôle mis à jour avec succès.',
            'data' => $typeRole,
        ]);
    }

    public function destroy(TypeRole $typeRole)
    {
        if ($typeRole->roles()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce type de rôle est associé à des rôles et ne peut pas être supprimé.',
            ], 409);
        }

        $typeRole->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type de rôle supprimé avec succès.',
        ]);
    }

    private function validated(Request $request, ?TypeRole $typeRole = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('type_roles', 'code')->ignore($typeRole)],
            'libelle' => ['required', 'string', 'max:100', Rule::unique('type_roles', 'libelle')->ignore($typeRole)],
            'description' => ['nullable', 'string', 'max:255'],
            'est_actif' => ['nullable', 'boolean'],
        ]);
    }
}
