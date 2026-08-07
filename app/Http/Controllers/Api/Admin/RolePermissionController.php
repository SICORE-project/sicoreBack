<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    /**
     * GET: /api/admin/role-permissions
     * Liste toutes les associations rôle-permission
     */
    public function index(Request $request)
    {
        $query = DB::table('role_permission')
            ->join('roles', 'role_permission.role_id', '=', 'roles.id')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->select(
                'role_permission.id',
                'role_permission.role_id',
                'roles.nom as role_nom',
                'roles.slug as role_slug',
                'role_permission.permission_id',
                'permissions.nom as permission_nom',
                'permissions.slug as permission_slug',
                'permissions.module',
                'permissions.groupe',
                'role_permission.created_at'
            );

        if ($request->has('role_id')) {
            $query->where('role_permission.role_id', $request->role_id);
        }

        if ($request->has('permission_id')) {
            $query->where('role_permission.permission_id', $request->permission_id);
        }

        if ($request->has('module')) {
            $query->where('permissions.module', $request->module);
        }

        $associations = $query->orderBy('roles.nom')
            ->orderBy('permissions.module')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $associations,
        ], 200);
    }

    /**
     * GET: /api/admin/role-permissions/role/{roleId}
     * Récupère toutes les permissions d'un rôle spécifique
     */
    public function getByRole($roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $permissions = $role->permissions()
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        // Récupérer aussi les permissions regroupées par module
        $permissionsByModule = $permissions->groupBy('module');

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role,
                'permissions' => $permissions,
                'permissions_by_module' => $permissionsByModule,
                'total' => $permissions->count(),
            ],
        ], 200);
    }

    /**
     * GET: /api/admin/role-permissions/permission/{permissionId}
     * Récupère tous les rôles associés à une permission
     */
    public function getByPermission($permissionId)
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        $roles = $permission->roles()
            ->orderBy('nom')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'permission' => $permission,
                'roles' => $roles,
                'total' => $roles->count(),
            ],
        ], 200);
    }

    /**
     * POST: /api/admin/role-permissions
     * Associer une permission à un rôle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::find($request->role_id);
        $permission = Permission::find($request->permission_id);

        // Vérifier si l'association existe déjà
        if ($role->permissions()->where('permission_id', $permission->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "La permission '{$permission->nom}' est déjà associée au rôle '{$role->nom}'.",
            ], 409);
        }

        // Ajouter la permission au rôle
        $role->permissions()->attach($permission->id);

        return response()->json([
            'success' => true,
            'message' => "Permission '{$permission->nom}' associée au rôle '{$role->nom}' avec succès.",
            'data' => [
                'role' => $role,
                'permission' => $permission,
            ],
        ], 201);
    }

    /**
     * POST: /api/admin/role-permissions/sync
     * Synchroniser toutes les permissions d'un rôle
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::find($request->role_id);
        $role->permissions()->sync($request->permission_ids);

        $permissions = $role->permissions()->get();

        return response()->json([
            'success' => true,
            'message' => 'Permissions synchronisées avec succès.',
            'data' => [
                'role' => $role,
                'permissions' => $permissions,
                'total' => $permissions->count(),
            ],
        ], 200);
    }

    /**
     * DELETE: /api/admin/role-permissions/{roleId}/{permissionId}
     * Supprimer une association rôle-permission
     */
    public function destroy($roleId, $permissionId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $permission = Permission::find($permissionId);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        // Vérifier si l'association existe
        if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "La permission '{$permission->nom}' n'est pas associée au rôle '{$role->nom}'.",
            ], 404);
        }

        $role->permissions()->detach($permission->id);

        return response()->json([
            'success' => true,
            'message' => "Permission '{$permission->nom}' retirée du rôle '{$role->nom}' avec succès.",
        ], 200);
    }

    /**
     * POST: /api/admin/role-permissions/bulk-assign
     * Assigner plusieurs permissions à un rôle
     */
    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::find($request->role_id);
        $count = 0;

        foreach ($request->permission_ids as $permissionId) {
            if (!$role->permissions()->where('permission_id', $permissionId)->exists()) {
                $role->permissions()->attach($permissionId);
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} permission(s) assignée(s) au rôle '{$role->nom}' avec succès.",
            'data' => [
                'role' => $role,
                'permissions' => $role->permissions()->get(),
            ],
        ], 200);
    }

    /**
     * POST: /api/admin/role-permissions/bulk-remove
     * Retirer plusieurs permissions d'un rôle
     */
    public function bulkRemove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::find($request->role_id);
        $count = count($request->permission_ids);

        $role->permissions()->detach($request->permission_ids);

        return response()->json([
            'success' => true,
            'message' => "{$count} permission(s) retirée(s) du rôle '{$role->nom}' avec succès.",
            'data' => [
                'role' => $role,
                'permissions' => $role->permissions()->get(),
            ],
        ], 200);
    }

    /**
     * GET: /api/admin/role-permissions/stats
     * Statistiques des associations
     */
    public function stats()
    {
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $totalAssociations = DB::table('role_permission')->count();

        // Nombre de permissions par rôle
        $permissionsPerRole = DB::table('role_permission')
            ->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                $role = Role::find($item->role_id);
                return [
                    'role_id' => $item->role_id,
                    'role_nom' => $role ? $role->nom : 'Inconnu',
                    'total' => $item->total,
                ];
            });

        // Nombre de rôles par permission
        $rolesPerPermission = DB::table('role_permission')
            ->select('permission_id', DB::raw('count(*) as total'))
            ->groupBy('permission_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $permission = Permission::find($item->permission_id);
                return [
                    'permission_id' => $item->permission_id,
                    'permission_nom' => $permission ? $permission->nom : 'Inconnu',
                    'permission_module' => $permission ? $permission->module : 'Inconnu',
                    'total' => $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_roles' => $totalRoles,
                'total_permissions' => $totalPermissions,
                'total_associations' => $totalAssociations,
                'permissions_per_role' => $permissionsPerRole,
                'top_permissions' => $rolesPerPermission,
                'taux_occupation' => $totalRoles > 0 && $totalPermissions > 0
                    ? round(($totalAssociations / ($totalRoles * $totalPermissions)) * 100, 2)
                    : 0,
            ],
        ], 200);
    }
}