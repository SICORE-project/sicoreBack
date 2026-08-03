<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Administration\UserService;
use App\Models\Admin\User;

class UserController extends Controller
{

    public function __construct(
        private UserService $userService
    )
    {

    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->userService
            ->all();

        return response()->json([

            'success'=>true,

            'message'=>'Liste des utilisateurs',

            'data'=> UserResource::collection($users)

        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
         $user = $this->userService
            ->create(
                $request->validated()
            );


        return response()->json([

            'success'=>true,

            'message'=>'Utilisateur créé avec succès.',

            'data'=> new UserResource($user)

        ],201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->userService
            ->find($id);

        return response()->json([

            'success'=>true,

            'message'=>'Utilisateur trouvé avec succès.',

            'data'=> new UserResource($user)

        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $this->userService
            ->find($id);

        $this->userService
            ->update($user, $request->validated());

        return response()->json([

            'success'=>true,

            'message'=>'Utilisateur mis à jour avec succès.',

            'data'=> new UserResource($user)

        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->userService
            ->find($id);

        $this->userService
            ->delete($user);

        return response()->json([

            'success'=>true,

            'message'=>'Utilisateur supprimé avec succès.'

        ],200);
    }
}
