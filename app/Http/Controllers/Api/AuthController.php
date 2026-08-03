<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\Auth\AuthService;
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
            'user'=>$request->user()
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
     * Demande de réinitialisation mot de passe
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {

        return $this->authService
            ->forgotPassword(
                $request->validated()
            );

    }

}