<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Administration\UserService;
use App\Models\Admin\User;
use Illuminate\Http\Request;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;

class UserController extends Controller
{

    public function __construct(
        private UserService $userService
    )
    {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->userService->all();

        return response()->json([
            'success' => true,
            'message' => 'Liste des utilisateurs',
            'data' => UserResource::collection($users)
        ], 200);
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

/**
 * Rattacher un utilisateur à une IA
 * POST /api/admin/users/{userId}/assign-ia/{iaId}
 */
public function assignUserToIa($userId, $iaId)
{
    try {
        // ✅ Appel au service
        $user = $this->userService->assignUserToIa($userId, $iaId);
        
        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire rattaché à l\'IA avec succès',
            'data' => new UserResource($user)
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
/**
 * Rattacher un utilisateur à une IEF
 * POST /api/admin/users/{userId}/assign-ief/{iefId}
 */
    public function assignUserToIef(int $userId, int $iefId): User
    {
        $user = User::findOrFail($userId);
        $ief = Ief::findOrFail($iefId);

        if (!$user->hasRole('gestionnaire_ief')) {
            throw new \Exception("Cet utilisateur n'a pas le rôle Gestionnaire IEF.");
        }

        if ($user->ief_id) {
            throw new \Exception("Cet utilisateur est déjà rattaché à une IEF.");
        }

        $user->ief_id = $iefId;
        $user->save();

        return $user->load(['ief', 'role']);
    }

    public function revokeUserFromIef(int $userId): User
    {
        $user = User::findOrFail($userId);

        if (!$user->ief_id) {
            throw new \Exception("Cet utilisateur n'est rattaché à aucune IEF.");
        }

        $user->ief_id = null;
        $user->save();

        return $user->load('role');
    }

    public function getUserIef(int $userId)
    {
        $user = User::with('ief')->findOrFail($userId);
        return $user->ief;
    }

    public function getGestionnairesIef()
    {
        return User::whereHas('role', function($query) {
            $query->where('slug', 'gestionnaire_ief');
        })->with(['ief', 'role'])->get();
    }

    public function getAvailableGestionnairesIef()
    {
        return User::whereHas('role', function($query) {
            $query->where('slug', 'gestionnaire_ief');
        })->whereNull('ief_id')->with('role')->get();
    }

    public function getGestionnairesByIef(int $iefId)
    {
        return User::whereHas('role', function($query) {
            $query->where('slug', 'gestionnaire_ief');
        })->where('ief_id', $iefId)->with(['ief', 'role'])->get();
    }


}