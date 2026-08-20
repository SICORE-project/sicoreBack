<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Rules\CompatibleRoleStructure;
use App\Services\Administration\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate(['type_structure' => ['nullable', 'string', 'max:50']]);
        $users = $this->userService->all($validated['type_structure'] ?? null);

        return UserResource::collection($users)->additional([
            'success' => true,
            'message' => 'Liste des utilisateurs',
        ]);
    }

    /**
     * Liste non paginée utilisée par les sélecteurs et l'écran historique.
     */
    public function all(Request $request)
    {
        $validated = $request->validate(['type_structure' => ['nullable', 'string', 'max:50']]);

        return UserResource::collection($this->userService->all($validated['type_structure'] ?? null))->additional([
            'success' => true,
            'message' => 'Liste des utilisateurs',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->userService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur trouvé avec succès.',
            'data' => new UserResource($user),
        ], 200);
    }

    public function assignRole(Request $request, string $id)
    {
        $user = $this->userService->find($id);

        $request->validate([
            'role_id' => [
                'required',
                Rule::exists('roles', 'id'),
                CompatibleRoleStructure::roleForStructure($user->lieu_service_id),
            ],
        ]);

        $user->update(['role_id' => $request->role_id]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle assigné avec succès.',
            'data' => new UserResource($user->load('role')),
        ], 200);
    }

    public function assignStructure(Request $request, string $id)
    {
        $user = $this->userService->find($id);

        $validated = $request->validate([
            'structure_organisationnelle_id' => [
                'required',
                'integer',
                Rule::exists('lieu_de_services', 'id')->where('est_actif', true),
                CompatibleRoleStructure::structureForRole($user->role_id),
            ],
        ]);

        $user = $this->userService->update($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur rattaché à la structure avec succès.',
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = $this->userService->find($id);
        $this->userService->update($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->userService->find($id);
        $this->userService->delete($user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ], 200);
    }

    public function toggleStatus(string $id)
    {
        $user = $this->userService->find($id);
        $user = $this->userService->update($user, [
            'statut' => $user->statut === 'actif' ? 'inactif' : 'actif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut de l’utilisateur mis à jour avec succès.',
            'data' => new UserResource($user),
        ]);
    }
}
