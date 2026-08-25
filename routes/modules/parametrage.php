<?php

use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\AffectationLieuServiceController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Http\Controllers\Api\Parametrage\DiplomeController;
use App\Http\Controllers\Api\Parametrage\DisciplineController;
use App\Http\Controllers\Api\Parametrage\IaController;
use App\Http\Controllers\Api\Parametrage\IefController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Http\Controllers\Api\Parametrage\PeriodePaieController;
use App\Http\Controllers\Api\Parametrage\RubriquePaieController;
use App\Http\Controllers\Api\Parametrage\SpecialiteController;
use App\Http\Controllers\Api\Parametrage\SyndicatController;
use Illuminate\Support\Facades\Route;

Route::get('parametrage/lieux-service', [LieuServiceController::class, 'catalogue'])
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

Route::post('enseignants/{enseignant}/affectations', [AffectationLieuServiceController::class, 'store'])
    ->whereNumber('enseignant')
    ->middleware('permission:parametrage.lieux_service.manage');

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
    Route::patch('/{id}/ia', [IefController::class, 'rattacherIa'])
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

/**
 * Routes for the Corps resource.
 */
Route::prefix('corps')->middleware('role:admin,super_admin')->group(function () {

    Route::get('/', [CorpsController::class, 'index']);
    Route::post('/', [CorpsController::class, 'store']);

    Route::get('/{id}', [CorpsController::class, 'show'])->whereNumber('id');

    Route::put('/{id}', [CorpsController::class, 'update'])->whereNumber('id');

    Route::patch('/{id}', [CorpsController::class, 'update'])->whereNumber('id');

    Route::delete('/{id}', [CorpsController::class, 'destroy'])->whereNumber('id');
});

Route::prefix('parametrage/disciplines')->middleware('role:admin,super_admin')->group(function () {
    Route::get('/', [DisciplineController::class, 'index']);
    Route::post('/', [DisciplineController::class, 'store']);
    Route::get('/{discipline}', [DisciplineController::class, 'show'])->whereNumber('discipline');
    Route::put('/{discipline}', [DisciplineController::class, 'update'])->whereNumber('discipline');
    Route::patch('/{discipline}/statut', [DisciplineController::class, 'updateStatus'])->whereNumber('discipline');
    Route::delete('/{discipline}', [DisciplineController::class, 'destroy'])->whereNumber('discipline');
});

Route::apiResource('categories', CategorieController::class)
    ->middleware('role:admin,super_admin');

Route::prefix('annees-academiques')->middleware('role:admin,super_admin')->group(function () {
    Route::get('/', [AnneeAcademiqueController::class, 'index']);
    Route::post('/', [AnneeAcademiqueController::class, 'store']);
    Route::get('/{id}', [AnneeAcademiqueController::class, 'show'])->whereNumber('id');
    Route::match(['put', 'patch'], '/{id}', [AnneeAcademiqueController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [AnneeAcademiqueController::class, 'destroy'])->whereNumber('id');
    Route::patch('/{id}/activate', [AnneeAcademiqueController::class, 'activate'])->whereNumber('id');
    Route::patch('/{id}/deactivate', [AnneeAcademiqueController::class, 'deactivate'])->whereNumber('id');
    Route::patch('/{id}/close', [AnneeAcademiqueController::class, 'close'])->whereNumber('id');
});

/*
|--------------------------------------------------------------------------
| RUBRIQUES DE PAIE
|--------------------------------------------------------------------------
| Gestion du référentiel des gains et retenues utilisés dans la paie.
*/
Route::prefix('rubriques-paie')->middleware('role:admin,super_admin')->group(function () {
    Route::get('/', [RubriquePaieController::class, 'index']);
    Route::post('/', [RubriquePaieController::class, 'store']);
    Route::get('/{id}', [RubriquePaieController::class, 'show'])->whereNumber('id');
    Route::match(['put', 'patch'], '/{id}', [RubriquePaieController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [RubriquePaieController::class, 'destroy'])->whereNumber('id');
});

Route::prefix('periodes-paie')->middleware('role:admin,super_admin')->group(function () {
    Route::get('/', [PeriodePaieController::class, 'index']);
    Route::post('/', [PeriodePaieController::class, 'store']);
    Route::get('/{id}', [PeriodePaieController::class, 'show'])->whereNumber('id');
    Route::match(['put', 'patch'], '/{id}', [PeriodePaieController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [PeriodePaieController::class, 'destroy'])->whereNumber('id');
});

Route::prefix('syndicats')->group(function () {
    Route::get('/', [SyndicatController::class, 'index'])
        ->middleware('permission:parametrage.syndicats.read');
    Route::post('/', [SyndicatController::class, 'store'])
        ->middleware('permission:parametrage.syndicats.manage');
    Route::get('/{id}', [SyndicatController::class, 'show'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.read');
    Route::put('/{id}', [SyndicatController::class, 'update'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}', [SyndicatController::class, 'update'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::delete('/{id}', [SyndicatController::class, 'destroy'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}/activate', [SyndicatController::class, 'activate'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}/deactivate', [SyndicatController::class, 'deactivate'])
        ->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
});

Route::prefix('iefs')->group(function () {
    Route::get('/', [IefController::class, 'index'])->middleware('permission:parametrage.ief.read');
    Route::post('/', [IefController::class, 'store'])->middleware('permission:parametrage.ief.manage');
    Route::get('/{id}', [IefController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.ief.read');
    Route::put('/{id}', [IefController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.ief.manage');
    Route::patch('/{id}/ia', [IefController::class, 'rattacherIa'])->whereNumber('id')->middleware('permission:parametrage.ief.manage');
    Route::delete('/{id}', [IefController::class, 'destroy'])->whereNumber('id')->middleware('role:admin,super_admin');
});

/*
|--------------------------------------------------------------------------
| INSPECTIONS D'ACADÉMIE (IA)
|--------------------------------------------------------------------------
| Gestion des IA et chargement du référentiel des régions.
*/
Route::prefix('ias')->group(function () {
    Route::get('/', [IaController::class, 'index'])->middleware('permission:parametrage.ia.read');
    Route::get('/regions', [IaController::class, 'regionOptions'])->middleware('permission:parametrage.ia.read');
    Route::get('/{id}/iefs', [IefController::class, 'byIa'])->whereNumber('id')->middleware('permission:parametrage.ief.read');
    Route::post('/', [IaController::class, 'store'])->middleware('permission:parametrage.ia.manage');
    Route::get('/{id}', [IaController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.ia.read');
    Route::put('/{id}', [IaController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.ia.manage');
    Route::delete('/{id}', [IaController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('role:admin,super_admin');
});

Route::prefix('specialites')->group(function () {
    Route::get('/', [SpecialiteController::class, 'index'])->middleware('permission:parametrage.specialites.read');
    Route::post('/', [SpecialiteController::class, 'store'])->middleware('permission:parametrage.specialites.manage');
    Route::put('/{id}', [SpecialiteController::class, 'update'])->middleware('permission:parametrage.specialites.manage');
    Route::patch('/{id}/statut', [SpecialiteController::class, 'changeStatus'])->middleware('permission:parametrage.specialites.manage');
    Route::get('/actives', [SpecialiteController::class, 'actives'])->middleware('permission:parametrage.specialites.read');
});
