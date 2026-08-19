<?php

use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
use App\Http\Controllers\Api\Parametrage\IaController;
use App\Http\Controllers\Api\Parametrage\IefController;
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Http\Controllers\Api\Parametrage\SyndicatController;
use Illuminate\Support\Facades\Route;

Route::prefix('parametrage/ia')->group(function () {
    Route::get('/', [IaController::class, 'index'])
        ->middleware('permission:parametrage.ia.read');
    Route::post('/', [IaController::class, 'store'])
        ->middleware('permission:parametrage.ia.manage');
    Route::patch('/{id}/statut', [IaController::class, 'changeStatus'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ia.manage');
    Route::get('/{id}', [IaController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ia.read');
    Route::put('/{id}', [IaController::class, 'update'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ia.manage');
    Route::delete('/{id}', [IaController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ia.manage');
    Route::get('/{id}/iefs', [IefController::class, 'byIa'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ief.read');
});

Route::prefix('parametrage/ief')->group(function () {
    Route::get('/', [IefController::class, 'index'])
        ->middleware('permission:parametrage.ief.read');
    Route::post('/', [IefController::class, 'store'])
        ->middleware('permission:parametrage.ief.manage');
    Route::get('/{id}', [IefController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ief.read');
    Route::put('/{id}', [IefController::class, 'update'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ief.manage');
    Route::patch('/{id}/statut', [IefController::class, 'changeStatus'])
        ->whereNumber('id')
        ->middleware('permission:parametrage.ief.manage');
});

/*
|--------------------------------------------------------------------------
| LIEUX DE SERVICE
|--------------------------------------------------------------------------
| Consultation, gestion et organisation territoriale des lieux de service.
*/
Route::prefix('lieux-service')->group(function () {
    // Consultation
    Route::get('/', [LieuServiceController::class, 'index'])
        ->middleware('permission:administration.users.read');
    Route::get('/manage', [LieuServiceController::class, 'manage'])
        ->middleware('permission:administration.users.read');

    // Référentiels et organisation territoriale
    Route::get('/ias', [LieuServiceController::class, 'iaOptions'])
        ->middleware('permission:administration.users.read');
    Route::get('/national', [LieuServiceController::class, 'national'])
        ->middleware('permission:administration.users.read');
    Route::get('/regions', [LieuServiceController::class, 'regions'])
        ->middleware('permission:administration.users.read');
    Route::get('/regions/{region}/ias', [LieuServiceController::class, 'ias']);
    Route::get('/ias/{ia}/iefs', [LieuServiceController::class, 'iefs'])
        ->whereNumber('ia');

    // Création, consultation, modification et suppression
    Route::post('/', [LieuServiceController::class, 'store'])
        ->middleware('permission:parametrage.lieux_service.manage');
    Route::get('/{lieuService}', [LieuServiceController::class, 'show'])
        ->whereNumber('lieuService')
        ->middleware('permission:administration.users.read');
    Route::put('/{lieuService}', [LieuServiceController::class, 'update'])
        ->whereNumber('lieuService')
        ->middleware('permission:parametrage.lieux_service.manage');
    Route::delete('/{lieuService}', [LieuServiceController::class, 'destroy'])
        ->whereNumber('lieuService')
        ->middleware('permission:parametrage.lieux_service.manage');
});

/*
|--------------------------------------------------------------------------
| CORPS
|--------------------------------------------------------------------------
| Gestion du référentiel des corps et de leur état d'activation.
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

/*
|--------------------------------------------------------------------------
| CATÉGORIES
|--------------------------------------------------------------------------
| Gestion du référentiel des catégories et de leur état d'activation.
*/
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
