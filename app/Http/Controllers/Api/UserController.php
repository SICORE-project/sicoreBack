<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Admin\User;
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
        $validated = $request->validate([
            'type_structure' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = $this->userService->paginate(
            $validated['per_page'] ?? 10,
            $validated['type_structure'] ?? null,
        );

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
     * Vérifier la disponibilité d'une adresse avant la soumission du formulaire.
     */
    public function checkEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $available = ! User::where('email', $data['email'])->exists();

        return response()->json([
            'available' => $available,
            'message' => $available ? null : 'Cette adresse e-mail est déjà utilisée.',
        ]);
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
            'lieu_service_id' => [
                'required',
                'integer',
                Rule::exists('lieu_de_services', 'id')->where('est_actif', true),
                CompatibleRoleStructure::structureForRole($user->role_id),
            ],
        ]);

        $user = $this->userService->update($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur rattaché au lieu de service avec succès.',
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

    public function assignUserToIa(string $userId, string $iaId)
    {
        $user = $this->userService->assignUserToIa((int) $userId, (int) $iaId);

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire rattaché à l\'IA avec succès.',
            'data' => new UserResource($user),
        ]);
    }

    public function revokeUserFromIa(string $userId)
    {
        $user = $this->userService->revokeUserFromIa((int) $userId);

        return response()->json([
            'success' => true,
            'message' => 'Rattachement à l\'IA révoqué avec succès.',
            'data' => new UserResource($user),
        ]);
    }
}
