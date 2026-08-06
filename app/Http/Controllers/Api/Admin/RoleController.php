<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * GET: /api/admin/roles
     * Liste des rôles
     */
    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('est_actif')) {
            $query->where('est_actif', $request->est_actif);
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        $roles = $query->orderBy('nom')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ], 200);
    }

    /**
     * GET: /api/admin/roles/all
     * Tous les rôles (sans pagination)
     */
    public function all()
    {
        $roles = Role::where('est_actif', true)->orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ], 200);
    }

    /**
     * POST: /api/admin/roles
     * Créer un rôle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:50|unique:roles',
            'slug' => 'required|string|max:50|unique:roles',
            'description' => 'nullable|string',
            'niveau' => 'required|in:systeme,admin_metier,gestionnaire,consultation',
            'est_actif' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'description' => $request->description,
            'niveau' => $request->niveau,
            'est_actif' => $request->est_actif ?? true,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rôle créé avec succès.',
            'data' => $role->load('permissions'),
        ], 201);
    }

    /**
     * GET: /api/admin/roles/{id}
     * Afficher un rôle
     */
    public function show($id)
    {
        $role = Role::with('permissions')->withCount('users')->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $role,
        ], 200);
    }

    /**
     * PUT: /api/admin/roles/{id}
     * Mettre à jour un rôle
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:50|unique:roles,nom,' . $id,
            'slug' => 'required|string|max:50|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'niveau' => 'required|in:systeme,admin_metier,gestionnaire,consultation',
            'est_actif' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->update([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'description' => $request->description,
            'niveau' => $request->niveau,
            'est_actif' => $request->est_actif ?? $role->est_actif,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour avec succès.',
            'data' => $role->load('permissions'),
        ], 200);
    }

    /**
     * DELETE: /api/admin/roles/{id}
     * Supprimer un rôle
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle est associé à des utilisateurs, il ne peut pas être supprimé.',
            ], 409);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rôle supprimé avec succès.',
        ], 200);
    }

    /**
     * POST: /api/admin/roles/{id}/sync-permissions
     * Synchroniser les permissions d'un rôle
     */
    public function syncPermissions(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->permissions()->sync($request->permissions);

        return response()->json([
            'success' => true,
            'message' => 'Permissions synchronisées avec succès.',
            'data' => $role->load('permissions'),
        ], 200);
    }

    /**
     * POST: /api/admin/roles/{id}/toggle-status
     * Activer/Désactiver un rôle
     */
    public function toggleStatus($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $role->est_actif = !$role->est_actif;
        $role->save();

        return response()->json([
            'success' => true,
            'message' => $role->est_actif ? 'Rôle activé avec succès.' : 'Rôle désactivé avec succès.',
            'data' => $role,
        ], 200);
    }
}