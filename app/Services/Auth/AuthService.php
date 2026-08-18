<?php

namespace App\Services\Auth;

use App\Models\admin\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


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



        // ⚠️ Changement : on charge aussi les permissions du rôle
        $user = User::with(['role.permissions', 'structureOrganisationnelle'])
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
                // 'role' inclut maintenant automatiquement 'permissions'
                // grâce au eager load with('role.permissions') ci-dessus
                'role'=>$user->role,
                'structure_organisationnelle'=>$user->structureOrganisationnelle ? [
                    'id'=>$user->structureOrganisationnelle->id,
                    'type'=>$user->structureOrganisationnelle->type,
                    'libelle'=>$user->structureOrganisationnelle->libelle,
                ] : null
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



        // Génération token
        $token = Str::random(64);



        // Suppression ancien token éventuel
        DB::table('password_reset_tokens')
            ->where('email',$user->email)
            ->delete();



        // Enregistrement token
        DB::table('password_reset_tokens')
            ->insert([

                'email'=>$user->email,

                /*
                Pour le MVP on stocke temporairement
                le token en clair.

                En production on utilisera Hash::make()
                */

                'token'=>$token,

                'created_at'=>Carbon::now()

            ]);



        return response()->json([

            'message'=>'Token de réinitialisation généré',

            // temporaire pour Postman uniquement
            'reset_token'=>$token

        ]);

    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(array $data)
    {
        // Vérification du token
        $record = DB::table('password_reset_tokens')
            ->where('email',$data['email'])
            ->first();

        // Vérification du token
        if(!$record)
        {
            return response()->json([
                'message'=>'Token invalide'
            ],400);
        }


        // Vérification de l'expiration du token
        if(
            Carbon::parse($record->created_at)
            ->addMinutes(60)
            ->isPast()
        )
        {
            // Suppression du token expiré
            return response()->json([
                'message'=>'Token expiré'
            ],400);

        }


        // Récupération de l'utilisateur
        $user = User::where(
            'email',
            $data['email']
        )->first();


        // Vérification de l'utilisateur
        if(!$user)
        {
            return response()->json([
                'message'=>'Utilisateur introuvable'
            ],404);
        }


        // Mise à jour du mot de passe
        $user->password =
            Hash::make($data['password']);


        $user->save();


        // Suppression du token
        DB::table('password_reset_tokens')
            ->where('email',$data['email'])
            ->delete();



        // Invalider les anciennes connexions
        $user->tokens()->delete();


        // Retour de la réponse
        return response()->json([
            'message'=>'Mot de passe réinitialisé avec succès'
        ]);

    }

}
