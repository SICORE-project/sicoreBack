<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordOtpRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    protected AuthService $authService;


    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }



    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {

        $request->validate([
            'login' => 'required_without:email',
            'email' => 'required_without:login|email',
            'password' => 'required|string'
        ]);


        return $this->authService->login(
            $request->all()
        );

    }



    /**
     * Utilisateur connecté
     */
    public function me(Request $request)
    {

        return response()->json([
            'user'=>new UserResource(
                $request->user()->loadMissing(['role', 'structureOrganisationnelle'])
            )
        ]);

    }



    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {

        return $this->authService->logout($request);

    }



    /**
     * Demande de réinitialisation mot de passe (flux "token" existant)
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {

        return $this->authService
            ->forgotPassword(
                $request->validated()
            );

    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService
            ->resetPassword(
                $request->validated()
            );
    }



    /**
     * Envoi du code OTP par email
     */
    public function sendOtp(SendOtpRequest $request)
    {
        return $this->authService
            ->sendOtp(
                $request->validated()
            );
    }



    /**
     * Vérification du code OTP
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        return $this->authService
            ->verifyOtp(
                $request->validated()
            );
    }



    /**
     * Réinitialisation du mot de passe via OTP vérifié
     */
    public function resetPasswordWithOtp(ResetPasswordOtpRequest $request)
    {
        return $this->authService
            ->resetPasswordWithOtp(
                $request->validated()
            );
    }

}
