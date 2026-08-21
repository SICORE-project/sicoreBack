<?php

use App\Http\Controllers\Api\Parametrage\CorpsController;
use \App\Http\Controllers\Api\Parametrage\SyndicatController;
use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
<<<<<<< HEAD
use App\Http\Controllers\Api\Parametrage\DiplomeController;
=======
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
>>>>>>> e72345b (Travail en cours LieuService dev-Amina-para)
use Illuminate\Support\Facades\Route;

Route::get('parametrage/lieux-service', [LieuServiceController::class, 'index'])
    ->middleware('permission:parametrage.lieux_service.read');

Route::get('parametrage/institutions-financieres', [InstitutFinancierController::class, 'index'])
    ->middleware('permission:parametrage.institutions_financieres.read');

Route::post('parametrage/institutions-financieres', [InstitutFinancierController::class, 'store'])
    ->middleware('permission:parametrage.institutions_financieres.manage');

Route::put('parametrage/institutions-financieres/{institution}', [InstitutFinancierController::class, 'update'])
    ->whereNumber('institution')
    ->middleware('permission:parametrage.institutions_financieres.manage');

Route::patch('parametrage/institutions-financieres/{institution}/statut', [InstitutFinancierController::class, 'updateStatut'])
    ->whereNumber('institution')
    ->middleware('permission:parametrage.institutions_financieres.manage');

Route::post('enseignants/{enseignant}/comptes-bancaires', [CompteBancaireEnseignantController::class, 'store'])
    ->whereNumber('enseignant')
    ->middleware('permission:enseignants.comptes_bancaires.manage');

/**
 * Routes for the Diplome resource.
 */
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])
    ->apiResource('diplomes', DiplomeController::class);
    
/**
 * Routes for the Corps resource.
 */
Route::prefix('corps')->group(function () {

    Route::get('/', [CorpsController::class, 'index']);

    Route::post('/', [CorpsController::class, 'store']);

    Route::get('/{id}', [CorpsController::class, 'show']);

    Route::put('/{id}', [CorpsController::class, 'update']);

    Route::patch('/{id}', [CorpsController::class, 'update']);

    Route::delete('/{id}', [CorpsController::class, 'destroy']);

    Route::patch('/{id}/activate', [
        CorpsController::class,
        'activate'
    ]);

    Route::patch('/{id}/deactivate', [
        CorpsController::class,
        'deactivate'
    ]);
});

// Routes for the Syndicat resource.
Route::prefix('syndicats')->group(function () {
    Route::get('/', [SyndicatController::class, 'index']);

    Route::post('/', [SyndicatController::class, 'store']);

    Route::get('/{id}', [SyndicatController::class, 'show']);

    Route::put('/{id}', [SyndicatController::class, 'update']);

    Route::patch('/{id}', [SyndicatController::class, 'update']);

    Route::delete('/{id}', [SyndicatController::class, 'destroy']);

    Route::patch('/{id}/activate', [
        SyndicatController::class,
        'activate'
    ]);

    Route::patch('/{id}/deactivate', [
        SyndicatController::class,
        'deactivate'
    ]);
});
