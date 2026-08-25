<?php

namespace App\Services\Auth;

use App\Mail\OtpMail;
use App\Models\admin\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\Mailer\Exception\TransportException;


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
        $user = User::with(['role.permissions', 'lieuService'])
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
                'lieu_service'=>$user->lieuService ? [
                    'id'=>$user->lieuService->id,
                    'type'=>$user->lieuService->type,
                    'libelle'=>$user->lieuService->libelle,
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
     * Préparation Forgot Password (flux "token" existant, conservé tel quel)
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
     * Réinitialisation du mot de passe (flux "token" existant, conservé tel quel)
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


    /**
     * Envoi d'un code OTP par email pour réinitialisation du mot de passe
     */
    public function sendOtp(array $data)
    {

        $user = User::where('email', $data['email'])->first();

        if (!$user)
        {
            throw ValidationException::withMessages([
                'email' => ['Aucun compte associé à cet email']
            ]);
        }

        // Génération d'un OTP à 6 chiffres
        $otp = (string) random_int(100000, 999999);

        // Suppression de toute demande précédente pour cet email
        DB::table('password_reset_otps')
            ->where('email', $user->email)
            ->delete();

        DB::table('password_reset_otps')->insert([
            'email'       => $user->email,
            'otp'         => Hash::make($otp),
            'reset_token' => null,
            'expires_at'  => Carbon::now()->addMinutes(10),
            'verified_at' => null,
            'created_at'  => Carbon::now(),
        ]);

        try {
            Mail::to($user->email)->send(new OtpMail($otp, $user));
        } catch (TransportException $e) {
            // Evite de garder un OTP actif si l'email n'a pas pu etre envoye.
            DB::table('password_reset_otps')
                ->where('email', $user->email)
                ->delete();

            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Echec de l\'envoi du code de verification (SMTP Gmail).',
                    'detail' => $e->getMessage(),
                    'step' => 'send-otp',
                ], 500);
            }

            return response()->json([
                'message' => 'Echec de l\'envoi du code de verification. Verifiez la configuration email Gmail.',
                'step' => 'send-otp',
            ], 500);
        }

        return response()->json([
            'message' => 'Code de verification envoye avec succes. Verifiez votre boite email.',
            'step' => 'send-otp'
        ]);

    }


    /**
     * Vérification du code OTP - renvoie un reset_token de courte durée
     */
    public function verifyOtp(array $data)
    {

        $record = DB::table('password_reset_otps')
            ->where('email', $data['email'])
            ->first();

        if (!$record)
        {
            throw ValidationException::withMessages([
                'otp' => ['Aucune demande OTP trouvee pour cet email. Lancez d\'abord send-otp.']
            ]);
        }

        if (Carbon::parse($record->expires_at)->isPast())
        {
            DB::table('password_reset_otps')
                ->where('email', $data['email'])
                ->delete();

            throw ValidationException::withMessages([
                'otp' => ['Code OTP expire. Demandez un nouveau code via send-otp.']
            ]);
        }

        if (!Hash::check($data['otp'], $record->otp))
        {
            throw ValidationException::withMessages([
                'otp' => ['Code OTP invalide. Verifiez le code recu par email.']
            ]);
        }

        // OTP valide : on délivre un reset_token de courte durée
        $resetToken = Str::random(64);

        DB::table('password_reset_otps')
            ->where('email', $data['email'])
            ->update([
                'reset_token' => Hash::make($resetToken),
                'verified_at' => Carbon::now(),
                'expires_at'  => Carbon::now()->addMinutes(15),
            ]);

        return response()->json([
            'message'     => 'Code OTP verifie avec succes. Utilisez le reset_token pour modifier le mot de passe.',
            'step'        => 'verify-otp',
            'reset_token' => $resetToken
        ]);

    }


    /**
     * Réinitialisation du mot de passe via reset_token issu de verifyOtp
     */
    public function resetPasswordWithOtp(array $data)
    {

        $record = DB::table('password_reset_otps')
            ->where('email', $data['email'])
            ->first();

        if (!$record || !$record->verified_at)
        {
            throw ValidationException::withMessages([
                'reset_token' => ['Verification OTP requise avant la modification du mot de passe.']
            ]);
        }

        if (Carbon::parse($record->expires_at)->isPast())
        {
            DB::table('password_reset_otps')
                ->where('email', $data['email'])
                ->delete();

            throw ValidationException::withMessages([
                'reset_token' => ['Session de reinitialisation expiree. Recommencez depuis send-otp.']
            ]);
        }

        if (!Hash::check($data['reset_token'], $record->reset_token))
        {
            throw ValidationException::withMessages([
                'reset_token' => ['Reset token invalide. Refaites la verification OTP.']
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user)
        {
            return response()->json([
                'message' => 'Utilisateur introuvable pour la modification du mot de passe.',
                'step' => 'reset-password-otp'
            ], 404);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        DB::table('password_reset_otps')
            ->where('email', $data['email'])
            ->delete();

        // Invalider les anciennes connexions
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe modifie avec succes. Vous pouvez maintenant vous connecter.',
            'step' => 'reset-password-otp'
        ]);

    }

}