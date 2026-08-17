<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/forgot-password', [AuthController::class,'forgotPassword']);

Route::post('/reset-password', [AuthController::class,'resetPassword']);

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
    

});