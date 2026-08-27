<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\PermissionGroupe;
use App\Models\Admin\PermissionModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                    ->orWhere('module', 'LIKE', "%{$search}%")
                    ->orWhere('groupe', 'LIKE', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('groupe')
            ->orderBy('module')
            ->orderBy('action')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/all
     * Toutes les permissions (sans pagination)
     */
    public function all()
    {
        $permissions = Permission::where('est_actif', true)
            ->orderBy('groupe')
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/modules
     * Liste des modules disponibles
     */
    public function getModules()
    {
        $modules = PermissionModule::with([
                'groupe:id,code,libelle',
                'permissions:id,nom,module,est_actif',
            ])
            ->withCount([
                'permissions',
                'permissions as permissions_actives_count' => fn ($query) => $query->where('est_actif', true),
            ])
            ->orderBy('libelle')->get()
            ->map(fn (PermissionModule $module) => [
                'id' => $module->id,
                'code' => $module->code,
                'libelle' => $module->libelle,
                'nom' => $module->libelle,
                // Conservation du format historique consommé par le frontend.
                'module' => $module->code,
                'groupe' => $module->groupe?->code,
                'groupe_id' => $module->groupe_id,
                'permissions_count' => $module->permissions_count,
                'permissions_actives_count' => $module->permissions_actives_count,
                'permissions' => $module->permissions->pluck('nom')->values(),
                'permissions_actives' => $module->permissions->where('est_actif', true)->pluck('nom')->values(),
                'statut_utilisation' => $module->permissions_count > 0 ? 'Utilisé' : 'Non utilisé',
                'est_actif' => $module->est_actif,
            ]);

        return response()->json([
            'success' => true,
            'data' => $modules,
        ], 200);
    }

    /** Liste des groupes utilisables dans les filtres et formulaires. */
    public function getGroupes()
    {
        return response()->json([
            'success' => true,
            'data' => PermissionGroupe::orderBy('libelle')->get(),
        ]);
    }

    /**
     * Toutes les options du formulaire de permission.
     * Les paramètres groupe et module permettent d'alimenter des listes liées.
     */
    public function getOptions(Request $request)
    {
        $modules = PermissionModule::with('groupe:id,code,libelle')
            ->where('est_actif', true)
            ->when($request->filled('groupe'), function ($query) use ($request) {
                $query->whereHas('groupe', fn ($q) => $q->where('code', $request->groupe));
            })
            ->orderBy('libelle')
            ->get();

        $actions = Permission::query()
            ->whereNotNull('action')
            ->where('action', '<>', '')
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->module))
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json([
            'success' => true,
            'data' => [
                'groupes' => PermissionGroupe::where('est_actif', true)->orderBy('libelle')->get(),
                'modules' => $modules,
                'actions' => $actions,
            ],
        ]);
    }

    /**
     * GET: /api/admin/permissions/module/{module}
     * Permissions d'un module spécifique
     */
    public function getByModule($module)
    {
        $permissions = Permission::where('module', $module)
            ->orderBy('action')
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
     * Permissions d'un groupe spécifique
     */
    public function getByGroupe($groupe)
    {
        $permissions = Permission::where('groupe', $groupe)
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * GET: /api/admin/permissions/actions/{module}
     * Actions disponibles pour un module
     */
    public function getActions($module)
    {
        $actions = Permission::where('module', $module)
            ->select('action')
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
            'slug' => 'required|string|max:100|unique:permissions',
            'groupe' => 'required|string|max:50|exists:permission_groupes,code',
            'module' => 'required|string|max:50|exists:permission_modules,code',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
            'est_actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = Permission::create([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'groupe' => $request->groupe,
            'module' => $request->module,
            'action' => $request->action,
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
            'slug' => 'required|string|max:100|unique:permissions,slug,' . $id,
            'groupe' => 'required|string|max:50|exists:permission_groupes,code',
            'module' => 'required|string|max:50|exists:permission_modules,code',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
            'est_actif' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission->update([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'groupe' => $request->groupe,
            'module' => $request->module,
            'action' => $request->action,
            'description' => $request->description,
            'est_actif' => $request->est_actif ?? $permission->est_actif,
        ]);

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
            ['nom' => 'Consulter les utilisateurs', 'slug' => 'administration.users.read', 'groupe' => 'administration', 'module' => 'users', 'action' => 'read'],
            ['nom' => 'Créer un utilisateur', 'slug' => 'administration.users.create', 'groupe' => 'administration', 'module' => 'users', 'action' => 'create'],
            ['nom' => 'Modifier un utilisateur', 'slug' => 'administration.users.update', 'groupe' => 'administration', 'module' => 'users', 'action' => 'update'],
            ['nom' => 'Supprimer un utilisateur', 'slug' => 'administration.users.delete', 'groupe' => 'administration', 'module' => 'users', 'action' => 'delete'],
            ['nom' => 'Consulter les rôles', 'slug' => 'administration.roles.read', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'read'],
            ['nom' => 'Gérer les rôles', 'slug' => 'administration.roles.manage', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'manage'],
            ['nom' => 'Consulter les permissions', 'slug' => 'administration.permissions.read', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'read'],
            ['nom' => 'Gérer les permissions', 'slug' => 'administration.permissions.manage', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'manage'],

            // Enseignants
            ['nom' => 'Consulter les enseignants', 'slug' => 'enseignants.read', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'read'],
            ['nom' => 'Créer un enseignant', 'slug' => 'enseignants.create', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'create'],
            ['nom' => 'Modifier un enseignant', 'slug' => 'enseignants.update', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'update'],
            ['nom' => 'Supprimer un enseignant', 'slug' => 'enseignants.delete', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'delete'],
            ['nom' => 'Valider un enseignant', 'slug' => 'enseignants.validate', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'validate'],
            ['nom' => 'Rechercher un enseignant', 'slug' => 'enseignants.search', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'search'],
            ['nom' => 'Exporter les enseignants', 'slug' => 'enseignants.export', 'groupe' => 'enseignants', 'module' => 'enseignants', 'action' => 'export'],

            // Paie
            ['nom' => 'Consulter les bulletins de paie', 'slug' => 'paie.bulletins.read', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'read'],
            ['nom' => 'Générer les bulletins de paie', 'slug' => 'paie.bulletins.generate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'generate'],
            ['nom' => 'Valider les bulletins de paie', 'slug' => 'paie.bulletins.validate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'validate'],

            // Indemnités
            ['nom' => 'Consulter les indemnités', 'slug' => 'indemnites.read', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'read'],
            ['nom' => 'Gérer les indemnités', 'slug' => 'indemnites.manage', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'manage'],
            ['nom' => 'Valider les indemnités', 'slug' => 'indemnites.validate', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'validate'],

            // Budget
            ['nom' => 'Consulter le budget', 'slug' => 'budget.read', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'read'],
            ['nom' => 'Gérer le budget', 'slug' => 'budget.manage', 'groupe' => 'budget', 'module' => 'budget', 'action' => 'manage'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($defaultPermissions as $perm) {
            $groupe = PermissionGroupe::firstOrCreate(
                ['code' => $perm['groupe']],
                ['libelle' => ucfirst(str_replace(['_', '-'], ' ', $perm['groupe'])), 'est_actif' => true]
            );
            PermissionModule::firstOrCreate(
                ['code' => $perm['module']],
                [
                    'libelle' => ucfirst(str_replace(['_', '-'], ' ', $perm['module'])),
                    'groupe_id' => $groupe->id,
                    'est_actif' => true,
                ]
            );

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

}
