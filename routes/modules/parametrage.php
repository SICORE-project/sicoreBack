<?php

use App\Http\Controllers\Api\Parametrage\IaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Paramétrage
|--------------------------------------------------------------------------
*/

Route::prefix('parametrage')->group(function () {

    // ============================================================
    // ROUTES IA (Inspection Académique)
    // ============================================================

    Route::prefix('ia')->group(function () {

        Route::get('/', [IaController::class, 'index'])->middleware('permission:parametrage.ia.read');
        Route::post('/', [IaController::class, 'store'])->middleware('permission:parametrage.ia.manage');
        Route::patch('/{id}/statut', [IaController::class, 'changeStatus'])->middleware('permission:parametrage.ia.manage');
        Route::get('/{id}', [IaController::class, 'show'])->middleware('permission:parametrage.ia.read');
        Route::put('/{id}', [IaController::class, 'update'])->middleware('permission:parametrage.ia.manage');
        Route::delete('/{id}', [IaController::class, 'destroy']);
    });

    // ============================================================
    // ROUTES IEF (à ajouter plus tard)
    // ============================================================

    // Route::prefix('ief')->group(function () {
    //     Route::get('/', [IefController::class, 'index']);
    //     Route::post('/', [IefController::class, 'store']);
    //     Route::get('/{id}', [IefController::class, 'show']);
    //     Route::put('/{id}', [IefController::class, 'update']);
    //     Route::delete('/{id}', [IefController::class, 'destroy']);
    // });

});