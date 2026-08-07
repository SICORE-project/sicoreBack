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

 
    Route::apiResource('indemnites', IndemnitesController::class);
    Route::apiResource('type-indemnites', TypeIndemnitesController::class);
    Route::apiResource('convocations', ConvocationsController::class);
    Route::apiResource('pieces-justificatives', PieceJustificativesController::class);
    Route::apiResource('bultins', BultinsController::class);
    Route::apiResource('detail-bultins', DetailBultinsController::class);
    Route::apiResource('rubrique-bultins', RubriqueBultinsController::class);
    Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class);
