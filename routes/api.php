<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiplomeController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');


 // Réinitialisation de mot de passe - flux "token" existant
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
 
    // Réinitialisation de mot de passe - flux OTP
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password-otp', [AuthController::class, 'resetPasswordWithOtp']);
    /*
    Ce qui équivaut à :
        GET    /api/diplomes            -> index
        POST   /api/diplomes            -> store
        GET    /api/diplomes/{diplome}  -> show
        PUT    /api/diplomes/{diplome}  -> update
        PATCH  /api/diplomes/{diplome}  -> update
        DELETE /api/diplomes/{diplome}  -> destroy
    */
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])
    ->apiResource('diplomes', DiplomeController::class);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);


    /*
    |--------------------------------------------------------------------------
    | MODULES
    |--------------------------------------------------------------------------
    */

    require __DIR__.'/modules/administration.php';
    require __DIR__.'/modules/parametrage.php';
    require __DIR__.'/modules/indemnites.php';

});
