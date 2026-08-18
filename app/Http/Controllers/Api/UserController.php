<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Administration\UserService;
use App\Models\Admin\User;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function __construct(
        private UserService $userService
    )
    {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = min(100, max(1, $request->integer('per_page', 10)));
        $users = $this->userService->paginate($perPage);

        return UserResource::collection($users)->additional([
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
            'data' => new UserResource($user)
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
            'data' => new UserResource($user)
        ], 200);
    }
 public function assignRole(Request $request, string $id)
{
    $user = $this->userService->find($id);

    $request->validate([
        'role_id' => 'required|exists:roles,id',
    ]);

    $user->update(['role_id' => $request->role_id]);

    return response()->json([
        'success' => true,
        'message' => 'Rôle assigné avec succès.',
        'data' => new UserResource($user->load('role'))
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
            'data' => new UserResource($user)
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
            'message' => 'Utilisateur supprimé avec succès.'
        ], 200);
    }
}
