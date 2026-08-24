<?php

use App\Http\Controllers\Api\Parametrage\AffectationLieuServiceController;
use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\DiplomeController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Http\Controllers\Api\Parametrage\SyndicatController;
use App\Http\Controllers\Api\Parametrage\IaController;
use App\Http\Controllers\Api\Parametrage\IefController;
use App\Http\Controllers\Api\Parametrage\SpecialiteController;
use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use Illuminate\Support\Facades\Route;

Route::get('parametrage/lieux-service', [LieuServiceController::class, 'index'])
    ->middleware('permission:parametrage.lieux_service.read');

Route::get('lieux-service', [LieuServiceController::class, 'index'])
    ->middleware('permission:parametrage.lieux_service.read');

Route::post('parametrage/lieux-service', [LieuServiceController::class, 'store'])
    ->middleware('permission:parametrage.lieux_service.manage');

Route::put('parametrage/lieux-service/{lieuService}', [LieuServiceController::class, 'update'])
    ->whereNumber('lieuService')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::patch('parametrage/lieux-service/{lieuService}/statut', [LieuServiceController::class, 'updateStatut'])
    ->whereNumber('lieuService')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::post('parametrage/enseignants/{enseignant}/affectations', [AffectationLieuServiceController::class, 'store'])
    ->whereNumber('enseignant')
    ->middleware('permission:parametrage.lieux_service.manage');

// Compatibilité avec le chemin historique utilisé par le frontend.
Route::post('enseignants/{enseignant}/affectations', [AffectationLieuServiceController::class, 'store'])
    ->whereNumber('enseignant')
    ->middleware('permission:parametrage.lieux_service.manage');

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
        'activate',
    ]);

    Route::patch('/{id}/deactivate', [
        CorpsController::class,
        'deactivate',
    ]);
});


Route::apiResource('categories', CategorieController::class);

Route::patch(
    'categories/{id}/activate',
    [CategorieController::class, 'activate']
);

Route::patch(
    'categories/{id}/deactivate',
    [CategorieController::class, 'deactivate']
);


Route::apiResource(
    'annees-academiques',
    AnneeAcademiqueController::class
);

Route::patch(
    'annees-academiques/{id}/activate',
    [AnneeAcademiqueController::class, 'activate']
);

Route::patch(
    'annees-academiques/{id}/deactivate',
    [AnneeAcademiqueController::class, 'deactivate']
);

Route::patch(
    'annees-academiques/{id}/close',
    [AnneeAcademiqueController::class, 'close']
);

Route::prefix('syndicats')->group(function () {
    Route::get('/', [SyndicatController::class, 'index']);
    Route::post('/', [SyndicatController::class, 'store']);
    Route::get('/{id}', [SyndicatController::class, 'show']);
    Route::put('/{id}', [SyndicatController::class, 'update']);
    Route::patch('/{id}', [SyndicatController::class, 'update']);
    Route::delete('/{id}', [SyndicatController::class, 'destroy']);
    Route::patch('/{id}/activate', [SyndicatController::class, 'activate']);
    Route::patch('/{id}/deactivate', [SyndicatController::class, 'deactivate']);
});

Route::prefix('iefs')->group(function () {
    Route::get('/', [IefController::class, 'index'])->middleware('permission:parametrage.ief.read');
    Route::post('/', [IefController::class, 'store'])->middleware('permission:parametrage.ief.manage');
    Route::get('/{id}', [IefController::class, 'show'])->middleware('permission:parametrage.ief.read');
    Route::get('/{id}/iefs', [IefController::class, 'byIa'])->middleware('permission:parametrage.ief.read');
    Route::put('/{id}', [IefController::class, 'update'])->middleware('permission:parametrage.ief.manage');
    Route::patch('/{id}/statut', [IefController::class, 'changeStatus'])->middleware('permission:parametrage.ief.manage');
    Route::patch('/{id}/ia', [IefController::class, 'rattacherIa'])->middleware('permission:parametrage.ief.manage');
});

Route::prefix('specialites')->group(function () {
    Route::get('/', [SpecialiteController::class, 'index'])->middleware('permission:parametrage.specialites.read');
    Route::post('/', [SpecialiteController::class, 'store'])->middleware('permission:parametrage.specialites.manage');
    Route::put('/{id}', [SpecialiteController::class, 'update'])->middleware('permission:parametrage.specialites.manage');
    Route::patch('/{id}/statut', [SpecialiteController::class, 'changeStatus'])->middleware('permission:parametrage.specialites.manage');
    Route::get('/actives', [SpecialiteController::class, 'actives'])->middleware('permission:parametrage.specialites.read');
});
