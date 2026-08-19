<?php

use App\Http\Controllers\Api\Parametrage\IaController;
use App\Http\Controllers\Api\Parametrage\IefController;
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
        Route::get('/{id}/iefs', [IefController::class, 'byIa'])->middleware('permission:parametrage.ief.read');

    });

    // ============================================================
// ROUTES IEF (Inspection de l'Éducation et de la Formation)
// ============================================================

    Route::prefix('ief')->group(function () {

        Route::get('/', [IefController::class, 'index'])->middleware('permission:parametrage.ief.read');
        Route::post('/', [IefController::class, 'store'])->middleware('permission:parametrage.ief.manage');
        Route::get('/{id}', [IefController::class, 'show'])->middleware('permission:parametrage.ief.read');
        Route::get('/{id}/iefs', [IefController::class, 'byIa'])->middleware('permission:parametrage.ief.read');
        Route::put('/{id}', [IefController::class, 'update'])->middleware('permission:parametrage.ief.manage');
        Route::patch('/{id}/statut', [IefController::class, 'changeStatus'])->middleware('permission:parametrage.ief.manage');
});     

});