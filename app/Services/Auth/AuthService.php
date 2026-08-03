<?php

namespace App\Services\Auth;

use App\Models\admin\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthService
{


    /**
     * Connexion utilisateur
     */
    public function login(array $data)
    {

        $identifier =
            $data['login']
            ?? $data['email'];



        $user = User::with('role')
            ->where('email',$identifier)
            ->first();



        if(
            !$user ||
            !Hash::check(
                $data['password'],
                $user->password
            )
        ){

            throw ValidationException::withMessages([
                'login'=>[
                    'Identifiants incorrects'
                ]
            ]);

        }



        if(!$user->role)
        {
            return response()->json([
                'message'=>'Aucun rôle associé à cet utilisateur'
            ],403);
        }



        // Nettoyage ancien token
        $user->tokens()
            ->where('name','sicore-ui')
            ->delete();



        $token =
            $user
            ->createToken('sicore-ui')
            ->plainTextToken;



        return response()->json([

            'message'=>'Connexion réussie',

            'access_token'=>$token,

            'token_type'=>'Bearer',

            'user'=>[
                'id'=>$user->id,
                'nom'=>$user->nom,
                'prenom'=>$user->prenom,
                'email'=>$user->email,
                'role'=>$user->role
            ]

        ]);

    }




    /**
     * Déconnexion
     */
    public function logout($request)
    {

        $request
            ->user()
            ->currentAccessToken()
            ->delete();



        return response()->json([
            'message'=>'Déconnexion réussie'
        ]);

    }




    /**
     * Préparation Forgot Password
     */
    public function forgotPassword(array $data)
    {

        $user = User::where(
            'email',
            $data['email']
        )->first();


        if(!$user)
        {
            return response()->json([
                'message'=>'Utilisateur introuvable'
            ],404);
        }


        return response()->json([
            'message'=>'Demande de réinitialisation prise en compte'
        ]);

    }

}