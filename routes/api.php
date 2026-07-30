<?php

use App\Http\Controllers\AuthController;
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

//Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('users/creation-options', [UserController::class, 'creationOptions']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('indemnites', IndemnitesController::class);
    Route::apiResource('type-indemnites', TypeIndemnitesController::class);
    Route::apiResource('convocations', ConvocationsController::class);
    Route::apiResource('pieces-justificatives', PieceJustificativesController::class);
    Route::apiResource('bultins', BultinsController::class);
    Route::apiResource('detail-bultins', DetailBultinsController::class);
    Route::apiResource('rubrique-bultins', RubriqueBultinsController::class);
    Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class);
//}
//);
