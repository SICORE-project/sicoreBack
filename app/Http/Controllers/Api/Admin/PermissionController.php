<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    /**
     * GET: /api/admin/permissions
     * Liste des permissions
     */
    public function index(Request $request)
    {
        $query = Permission::query();

        if ($request->has('groupe')) {
            $query->where('groupe', $request->groupe);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('est_actif')) {
            $query->where('est_actif', $request->est_actif);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('nom')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/all
     * Toutes les permissions (sans pagination), actives ET inactives —
     * c'est au frontend de filtrer par statut si besoin.
     */
    public function all()
    {
        $permissions = Permission::orderBy('nom')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/modules
     * Liste des modules disponibles (legacy — conservé pour compatibilité)
     */
    public function getModules()
    {
        $modules = Permission::select('module', 'groupe')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('groupe')
            ->orderBy('module')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/module/{module}
     * Permissions d'un module spécifique (legacy — conservé pour compatibilité)
     */
    public function getByModule($module)
    {
        $permissions = Permission::where('module', $module)
            ->orderBy('nom')
            ->get();

        if ($permissions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Module non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/groupe/{groupe}
     * Permissions d'un groupe spécifique (legacy — conservé pour compatibilité)
     */
    public function getByGroupe($groupe)
    {
        $permissions = Permission::where('groupe', $groupe)
            ->orderBy('nom')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/actions/{module}
     * Actions disponibles pour un module (legacy — conservé pour compatibilité)
     */
    public function getActions($module)
    {
        $actions = Permission::where('module', $module)
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->pluck('action');

        return response()->json([
            'success' => true,
            'data' => $actions,
        ], 200);
    }

    /**
     * POST: /api/admin/permissions
     * Créer une permission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:permissions',
            'description' => 'nullable|string',
            'est_actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $slug = $this->uniquePermissionSlug($request->nom);

        $permission = Permission::create([
            'nom' => $request->nom,
            'slug' => $slug,
            'description' => $request->description,
            'est_actif' => $request->est_actif ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission créée avec succès.',
            'data' => $permission,
        ], 201);
    }

    /**
     * GET: /api/admin/permissions/{id}
     * Afficher une permission
     */
    public function show($id)
    {
        $permission = Permission::with('roles')->find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $permission,
        ], 200);
    }

    /**
     * PUT: /api/admin/permissions/{id}
     * Mettre à jour une permission
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:permissions,nom,' . $id,
            'description' => 'nullable|string',
            'est_actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'nom' => $request->nom,
            'description' => $request->description,
            'est_actif' => $request->est_actif ?? $permission->est_actif,
        ];

        /*
        |--------------------------------------------------------------------------
        | Ne régénérer le slug que si le nom a changé, pour éviter
        | de casser des références externes au slug existant.
        |--------------------------------------------------------------------------
        */
        if ($request->nom !== $permission->nom) {
            $data['slug'] = $this->uniquePermissionSlug($request->nom, $id);
        }

        $permission->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Permission mise à jour avec succès.',
            'data' => $permission,
        ], 200);
    }

    /**
     * DELETE: /api/admin/permissions/{id}
     * Supprimer une permission
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cette permission est associée à des rôles, elle ne peut pas être supprimée.',
            ], 409);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission supprimée avec succès.',
        ], 200);
    }

    /**
     * POST: /api/admin/permissions/sync
     * Synchroniser les permissions par défaut
     */
    public function sync()
    {
        $defaultPermissions = [
            // Administration
            ['nom' => 'Consulter les utilisateurs', 'slug' => 'administration_users_read', 'groupe' => 'administration', 'module' => 'users', 'action' => 'read'],
            ['nom' => 'Créer un utilisateur', 'slug' => 'administration_users_create', 'groupe' => 'administration', 'module' => 'users', 'action' => 'create'],
            ['nom' => 'Modifier un utilisateur', 'slug' => 'administration_users_update', 'groupe' => 'administration', 'module' => 'users', 'action' => 'update'],
            ['nom' => 'Supprimer un utilisateur', 'slug' => 'administration_users_delete', 'groupe' => 'administration', 'module' => 'users', 'action' => 'delete'],
            ['nom' => 'Consulter les rôles', 'slug' => 'administration_roles_read', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'read'],
            ['nom' => 'Gérer les rôles', 'slug' => 'administration_roles_manage', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'manage'],
            ['nom' => 'Consulter les permissions', 'slug' => 'administration_permissions_read', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'read'],
            ['nom' => 'Gérer les permissions', 'slug' => 'administration_permissions_manage', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'manage'],

            // Enseignants
            ['nom' => 'Consulter les enseignants', 'slug' => 'enseignants_read', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'read'],
            ['nom' => 'Créer un enseignant', 'slug' => 'enseignants_create', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'create'],
            ['nom' => 'Modifier un enseignant', 'slug' => 'enseignants_update', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'update'],
            ['nom' => 'Supprimer un enseignant', 'slug' => 'enseignants_delete', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'delete'],
            ['nom' => 'Valider un enseignant', 'slug' => 'enseignants_validate', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'validate'],
            ['nom' => 'Rechercher un enseignant', 'slug' => 'enseignants_search', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'search'],
            ['nom' => 'Exporter les enseignants', 'slug' => 'enseignants_export', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'export'],

            // Paie
            ['nom' => 'Consulter les bulletins de paie', 'slug' => 'paie_bulletins_read', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'read'],
            ['nom' => 'Générer les bulletins de paie', 'slug' => 'paie_bulletins_generate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'generate'],
            ['nom' => 'Valider les bulletins de paie', 'slug' => 'paie_bulletins_validate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'validate'],

            // Indemnités
            ['nom' => 'Consulter les indemnités', 'slug' => 'indemnites_read', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'read'],
            ['nom' => 'Gérer les indemnités', 'slug' => 'indemnites_manage', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'manage'],
            ['nom' => 'Valider les indemnités', 'slug' => 'indemnites_validate', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'validate'],

            // Budget
            ['nom' => 'Consulter le budget', 'slug' => 'budget_read', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'read'],
            ['nom' => 'Gérer le budget', 'slug' => 'budget_manage', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'manage'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($defaultPermissions as $perm) {
            $existing = Permission::where('slug', $perm['slug'])->first();
            if ($existing) {
                $existing->update($perm);
                $updated++;
            } else {
                Permission::create($perm);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synchronisation terminée : {$created} créées, {$updated} mises à jour.",
            'data' => [
                'created' => $created,
                'updated' => $updated,
            ],
        ], 200);
    }

    /**
     * POST: /api/admin/permissions/{id}/assign-role
     * Assigner une permission à un rôle
     */
    public function assignToRole(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'assign' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = \App\Models\Admin\Role::find($request->role_id);

        if ($request->assign) {
            $role->permissions()->attach($permission->id);
            $message = "Permission '{$permission->nom}' assignée au rôle '{$role->nom}'.";
        } else {
            $role->permissions()->detach($permission->id);
            $message = "Permission '{$permission->nom}' retirée du rôle '{$role->nom}'.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'permission' => $permission,
                'role' => $role,
            ],
        ], 200);
    }

    /**
     * Générer un slug unique pour une permission à partir de son nom.
     * $excludeId permet d'ignorer la permission elle-même lors d'une mise à jour.
     */
    private function uniquePermissionSlug(mixed $name, ?int $excludeId = null): string
    {
        $base = Str::of((string) $name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        $slug = $base;
        $counter = 1;

        while (
            Permission::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '_' . $counter;
            $counter++;
        }

        return $slug;
    }
}