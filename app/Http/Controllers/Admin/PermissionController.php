<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Afficher la liste des permissions
     */
    public function index(Request $request)
    {
        $permissions = Permission::query();

        // Filtrer par groupe
        if ($request->has('groupe') && $request->groupe) {
            $permissions->where('groupe', $request->groupe);
        }

        // Filtrer par module
        if ($request->has('module') && $request->module) {
            $permissions->where('module', $request->module);
        }

        $permissions = $permissions->orderBy('groupe')->orderBy('module')->orderBy('action')->get();

        // Grouper par module pour l'affichage
        $permissionsByModule = $permissions->groupBy(function ($item) {
            return $item->module;
        });

        // Liste des groupes pour le filtre
        $groupes = Permission::select('groupe')->distinct()->pluck('groupe');

        return view('admin.permissions.index', compact('permissions', 'permissionsByModule', 'groupes'));
    }

    /**
     * Afficher les permissions d'un module spécifique
     */
    public function byModule($module)
    {
        $permissions = Permission::where('module', $module)->orderBy('action')->get();

        if ($permissions->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Module non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'module' => $module,
            'permissions' => $permissions
        ]);
    }

    /**
     * Afficher les permissions d'un groupe spécifique
     */
    public function byGroupe($groupe)
    {
        $permissions = Permission::where('groupe', $groupe)
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        return response()->json([
            'success' => true,
            'groupe' => $groupe,
            'permissions' => $permissions
        ]);
    }

    /**
     * Afficher le formulaire de création de permission
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Enregistrer une nouvelle permission
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:100|unique:permissions',
            'slug' => 'required|string|max:100|unique:permissions',
            'groupe' => 'required|string|max:50',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'groupe' => $request->groupe,
            'module' => $request->module,
            'action' => $request->action,
            'description' => $request->description,
            'est_actif' => $request->has('est_actif'),
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission créée avec succès.');
    }

    /**
     * Afficher une permission
     */
    public function show(Permission $permission)
    {
        $roles = Role::with('permissions')->get();
        return view('admin.permissions.show', compact('permission', 'roles'));
    }

    /**
     * Afficher le formulaire d'édition d'une permission
     */
    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Mettre à jour une permission
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'nom' => 'required|string|max:100|unique:permissions,nom,' . $permission->id,
            'slug' => 'required|string|max:100|unique:permissions,slug,' . $permission->id,
            'groupe' => 'required|string|max:50',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $permission->update([
            'nom' => $request->nom,
            'slug' => $request->slug,
            'groupe' => $request->groupe,
            'module' => $request->module,
            'action' => $request->action,
            'description' => $request->description,
            'est_actif' => $request->has('est_actif'),
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission mise à jour avec succès.');
    }

    /**
     * Supprimer une permission
     */
    public function destroy(Permission $permission)
    {
        // Vérifier si la permission est utilisée par des rôles
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Cette permission est associée à des rôles, elle ne peut pas être supprimée.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission supprimée avec succès.');
    }

    /**
     * Assigner une permission à un rôle
     */
    public function assignToRole(Request $request, Permission $permission)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'assign' => 'required|boolean',
        ]);

        $role = Role::find($request->role_id);

        if ($request->assign) {
            $role->givePermission($permission->id);
            $message = 'Permission assignée au rôle ' . $role->nom;
        } else {
            $role->removePermission($permission->id);
            $message = 'Permission retirée du rôle ' . $role->nom;
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Obtenir la liste des modules disponibles
     */
    public function modules()
    {
        $modules = Permission::select('module', 'groupe')
            ->distinct()
            ->orderBy('groupe')
            ->orderBy('module')
            ->get();

        return response()->json([
            'success' => true,
            'modules' => $modules
        ]);
    }

    /**
     * Obtenir les actions disponibles pour un module
     */
    public function actions($module)
    {
        $permissions = Permission::where('module', $module)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return response()->json([
            'success' => true,
            'module' => $module,
            'actions' => $permissions
        ]);
    }

    /**
     * Synchroniser toutes les permissions (si des permissions manquent)
     * Utilisé pour mettre à jour les permissions après un ajout de fonctionnalité
     */
    public function sync()
    {
        // Liste des permissions par défaut
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
            ['nom' => 'Consulter les rubriques de paie', 'slug' => 'paie.rubriques.read', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'read'],
            ['nom' => 'Gérer les rubriques de paie', 'slug' => 'paie.rubriques.manage', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'manage'],

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
            $existing = Permission::where('slug', $perm['slug'])->first();
            if ($existing) {
                $existing->update($perm);
                $updated++;
            } else {
                Permission::create($perm);
                $created++;
            }
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', "Synchronisation terminée : {$created} créées, {$updated} mises à jour.");
    }
}