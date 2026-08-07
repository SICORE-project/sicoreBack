<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BultinsController;
use App\Http\Controllers\ConvocationsController;
use App\Http\Controllers\DetailBultinsController;
use App\Http\Controllers\EtatPaieIndemnitesController;
use App\Http\Controllers\IndemnitesController;
use App\Http\Controllers\PieceJustificativesController;
use App\Http\Controllers\RubriqueBultinsController;
use App\Http\Controllers\TypeIndemnitesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/forgot-password', [AuthController::class,'forgotPassword']);

Route::post('/reset-password', [AuthController::class,'resetPassword']);

Route::middleware([
    'auth:sanctum',
])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Route::get('/test-role', function () {

    //     return response()->json([
    //         'message' => 'Bienvenue administrateur'
    //     ]);

    // });


    // Route::get('/test-permission', function () {
    //     return response()->json([
    //         'message' => 'Accès autorisé'
    //     ]);
    // });

    // Route::apiResource('users', UserController::class);


    require __DIR__.'/modules/administration.php';
    require __DIR__.'/modules/indemnites.php';

   
});
