<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;
use App\Models\Admin\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * GET: /api/admin/users
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('prenom', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $users = $query->orderBy('nom')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    /**
     * GET: /api/admin/users/all
     * Tous les utilisateurs (sans pagination)
     */
    public function all()
    {
        $users = User::with('role')->orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    /**
     * POST: /api/admin/users
     * Créer un utilisateur
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|in:actif,inactif,bloque',
            'fonction' => 'nullable|string|max:100',
            'genre' => 'nullable|in:masculin,feminin,non_precise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'statut' => $request->statut ?? 'actif',
            'fonction' => $request->fonction,
            'genre' => $request->genre ?? 'non_precise',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load('role'),
        ], 201);
    }

    /**
     * GET: /api/admin/users/{id}
     * Afficher un utilisateur
     */
    public function show($id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * PUT: /api/admin/users/{id}
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'nullable|string|max:50',
            'prenom' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'nullable|in:actif,inactif,bloque',
            'fonction' => 'nullable|string|max:100',
            'genre' => 'nullable|in:masculin,feminin,non_precise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['nom', 'prenom', 'email', 'role_id', 'statut', 'fonction', 'genre']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => $user->load('role'),
        ], 200);
    }

    /**
     * DELETE: /api/admin/users/{id}
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ], 200);
    }

    /**
     * POST: /api/admin/users/{id}/assign-role
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update(['role_id' => $request->role_id]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle assigné avec succès.',
            'data' => $user->load('role'),
        ], 200);
    }

    /**
     * POST: /api/admin/users/{id}/toggle-status
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut.',
            ], 403);
        }

        $newStatus = $user->statut === 'actif' ? 'inactif' : 'actif';
        $user->update(['statut' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Utilisateur {$newStatus} avec succès.",
            'data' => $user,
        ], 200);
    }
}