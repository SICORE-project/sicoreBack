<?php

use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\AffectationLieuServiceController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Http\Controllers\Api\Parametrage\DiplomeController;
use App\Http\Controllers\Api\Parametrage\SpecialiteEnseignantController;
use App\Http\Controllers\Api\Parametrage\IaController;
use App\Http\Controllers\Api\Parametrage\IefController;
use App\Http\Controllers\Api\Parametrage\GradeController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Http\Controllers\Api\Parametrage\PeriodePaieController;
use App\Http\Controllers\Api\Parametrage\RubriquePaieController;
use App\Http\Controllers\Api\Parametrage\SpecialiteController;
use App\Http\Controllers\Api\Parametrage\SyndicatController;
use App\Http\Controllers\Api\Parametrage\RegionController;
use App\Http\Controllers\Api\Parametrage\DepartementController;
use App\Http\Controllers\Api\Parametrage\CommuneController;
use Illuminate\Support\Facades\Route;

Route::get('parametrage/lieux-service', [LieuServiceController::class, 'catalogue'])
    ->middleware('permission:parametrage.lieux_service.read');

Route::post('parametrage/lieux-service', [LieuServiceController::class, 'store'])
    ->middleware('permission:parametrage.lieux_service.manage');

Route::put('parametrage/lieux-service/{lieuService}', [LieuServiceController::class, 'update'])
    ->whereNumber('lieuService')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::delete('parametrage/lieux-service/{lieuService}', [LieuServiceController::class, 'destroy'])
    ->whereNumber('lieuService')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::patch('parametrage/lieux-service/{lieuService}/statut', [LieuServiceController::class, 'updateStatut'])
    ->whereNumber('lieuService')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::post('parametrage/enseignants/{enseignant}/affectations', [AffectationLieuServiceController::class, 'store'])
    ->middleware(\App\Http\Middleware\DrhPersonnelAccess::class)
    ->whereNumber('enseignant')
    ->middleware('permission:parametrage.lieux_service.manage');

Route::post('enseignants/{enseignant}/affectations', [AffectationLieuServiceController::class, 'store'])
    ->middleware(\App\Http\Middleware\DrhPersonnelAccess::class)
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
| ÉTABLISSEMENTS
|--------------------------------------------------------------------------
| Consultation, gestion et organisation territoriale des établissements.
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
Route::prefix('corps')->group(function () {
    Route::get('/', [CorpsController::class, 'index'])->middleware('permission:enseignants.create,parametrage.corps.read');
    Route::get('/{id}', [CorpsController::class, 'show'])->whereNumber('id')->middleware('permission:enseignants.create,parametrage.corps.read');
    Route::post('/', [CorpsController::class, 'store'])->middleware('permission:parametrage.corps.manage');
    Route::put('/{id}', [CorpsController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.corps.manage');
    Route::patch('/{id}', [CorpsController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.corps.manage');
    Route::delete('/{id}', [CorpsController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.corps.manage');
});

Route::prefix('grades')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->middleware('permission:enseignants.create,parametrage.corps.read');
    Route::get('/{id}', [GradeController::class, 'show'])->whereNumber('id')->middleware('permission:enseignants.create,parametrage.corps.read');
    Route::post('/', [GradeController::class, 'store'])->middleware('permission:parametrage.corps.manage');
    Route::match(['put', 'patch'], '/{id}', [GradeController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.corps.manage');
    Route::delete('/{id}', [GradeController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.corps.manage');
});

Route::prefix('parametrage/disciplines')->group(function () {
    Route::get('/', [SpecialiteEnseignantController::class, 'index'])->middleware('permission:parametrage.disciplines.read');
    Route::post('/', [SpecialiteEnseignantController::class, 'store'])->middleware('permission:parametrage.disciplines.manage');
    Route::get('/{discipline}', [SpecialiteEnseignantController::class, 'show'])->whereNumber('discipline')->middleware('permission:parametrage.disciplines.read');
    Route::put('/{discipline}', [SpecialiteEnseignantController::class, 'update'])->whereNumber('discipline')->middleware('permission:parametrage.disciplines.manage');
    Route::patch('/{discipline}/statut', [SpecialiteEnseignantController::class, 'updateStatus'])->whereNumber('discipline')->middleware('permission:parametrage.disciplines.manage');
    Route::delete('/{discipline}', [SpecialiteEnseignantController::class, 'destroy'])->whereNumber('discipline')->middleware('permission:parametrage.disciplines.manage');
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategorieController::class, 'index'])->middleware('permission:enseignants.create,parametrage.categories.read');
    Route::get('/{id}', [CategorieController::class, 'show'])->whereNumber('id')->middleware('permission:enseignants.create,parametrage.categories.read');
    Route::post('/', [CategorieController::class, 'store'])->middleware('permission:parametrage.categories.manage');
    Route::match(['put', 'patch'], '/{id}', [CategorieController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.categories.manage');
    Route::delete('/{id}', [CategorieController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.categories.manage');
});

Route::prefix('annees-academiques')->group(function () {
    Route::get('/', [AnneeAcademiqueController::class, 'index'])->middleware('permission:parametrage.annees_academiques.read');
    Route::post('/', [AnneeAcademiqueController::class, 'store'])->middleware('permission:parametrage.annees_academiques.manage');
    Route::get('/{id}', [AnneeAcademiqueController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.read');
    Route::match(['put', 'patch'], '/{id}', [AnneeAcademiqueController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.manage');
    Route::delete('/{id}', [AnneeAcademiqueController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.manage');
    Route::patch('/{id}/activate', [AnneeAcademiqueController::class, 'activate'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.manage');
    Route::patch('/{id}/deactivate', [AnneeAcademiqueController::class, 'deactivate'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.manage');
    Route::patch('/{id}/close', [AnneeAcademiqueController::class, 'close'])->whereNumber('id')->middleware('permission:parametrage.annees_academiques.manage');
});

Route::prefix('parametrage/institutions-financieres')->group(function (): void {
    Route::get('/', [InstitutFinancierController::class, 'index'])
        ->middleware('permission:parametrage.institutions_financieres.read');

    Route::post('/', [InstitutFinancierController::class, 'store'])
        ->middleware('permission:parametrage.institutions_financieres.manage');

    Route::put('/{institution}', [InstitutFinancierController::class, 'update'])
        ->whereNumber('institution')
        ->middleware('permission:parametrage.institutions_financieres.manage');

    Route::patch('/{institution}/statut', [InstitutFinancierController::class, 'updateStatut'])
        ->whereNumber('institution')
        ->middleware('permission:parametrage.institutions_financieres.manage');

    Route::delete('/{institution}', [InstitutFinancierController::class, 'destroy'])
        ->whereNumber('institution')
        ->middleware('permission:parametrage.institutions_financieres.manage');
});

/*
|--------------------------------------------------------------------------
| RUBRIQUES DE PAIE
|--------------------------------------------------------------------------
| Gestion du référentiel des gains et retenues utilisés dans la paie.
*/
Route::prefix('rubriques-paie')->group(function () {
    Route::get('/', [RubriquePaieController::class, 'index'])->middleware('permission:paie.rubriques.read');
    Route::post('/', [RubriquePaieController::class, 'store'])->middleware('permission:paie.rubriques.manage');
    Route::get('/{id}', [RubriquePaieController::class, 'show'])->whereNumber('id')->middleware('permission:paie.rubriques.read');
    Route::match(['put', 'patch'], '/{id}', [RubriquePaieController::class, 'update'])->whereNumber('id')->middleware('permission:paie.rubriques.manage');
    Route::delete('/{id}', [RubriquePaieController::class, 'destroy'])->whereNumber('id')->middleware('permission:paie.rubriques.manage');
});

Route::prefix('periodes-paie')->group(function () {
    Route::get('/', [PeriodePaieController::class, 'index'])->middleware('permission:parametrage.periodes_paie.read');
    Route::post('/', [PeriodePaieController::class, 'store'])->middleware('permission:parametrage.periodes_paie.manage');
    Route::get('/{id}', [PeriodePaieController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.periodes_paie.read');
    Route::match(['put', 'patch'], '/{id}', [PeriodePaieController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.periodes_paie.manage');
    Route::delete('/{id}', [PeriodePaieController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.periodes_paie.manage');
});

Route::prefix('syndicats')->group(function () {
    Route::get('/', [SyndicatController::class, 'index']) ->middleware('permission:parametrage.syndicats.read');
    Route::post('/', [SyndicatController::class, 'store']) ->middleware('permission:parametrage.syndicats.manage');
    Route::get('/{id}', [SyndicatController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.syndicats.read');
    Route::put('/{id}', [SyndicatController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}', [SyndicatController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::delete('/{id}', [SyndicatController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}/activate', [SyndicatController::class, 'activate'])->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
    Route::patch('/{id}/deactivate', [SyndicatController::class, 'deactivate'])->whereNumber('id')->middleware('permission:parametrage.syndicats.manage');
});

Route::prefix('iefs')->group(function () {
    Route::get('/', [IefController::class, 'index'])->middleware('permission:parametrage.ief.read');
    Route::post('/', [IefController::class, 'store'])->middleware('permission:parametrage.ief.manage');
    Route::get('/{id}', [IefController::class, 'show'])->whereNumber('id')->middleware('permission:parametrage.ief.read');
    Route::put('/{id}', [IefController::class, 'update'])->whereNumber('id')->middleware('permission:parametrage.ief.manage');
    Route::patch('/{id}/ia', [IefController::class, 'rattacherIa'])->whereNumber('id')->middleware('permission:parametrage.ief.manage');
    Route::delete('/{id}', [IefController::class, 'destroy'])->whereNumber('id')->middleware('permission:parametrage.ief.delete');
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
        ->middleware('permission:parametrage.ia.delete');
});

Route::prefix('specialites')->group(function () {
    Route::get('/', [SpecialiteController::class, 'index'])->middleware('permission:parametrage.specialites.read');
    Route::post('/', [SpecialiteController::class, 'store'])->middleware('permission:parametrage.specialites.manage');
    Route::put('/{id}', [SpecialiteController::class, 'update'])->middleware('permission:parametrage.specialites.manage');
    Route::patch('/{id}/statut', [SpecialiteController::class, 'changeStatus'])->middleware('permission:parametrage.specialites.manage');
    Route::get('/actives', [SpecialiteController::class, 'actives'])->middleware('permission:parametrage.specialites.read');
});

Route::prefix('statuts-enseignant')->group(function () {
    $controller = \App\Http\Controllers\Api\Parametrage\StatutEnseignantController::class;
    Route::get('/', [$controller, 'index'])->middleware('permission:enseignants.create');
    Route::get('/{id}', [$controller, 'show'])->whereNumber('id')->middleware('permission:enseignants.create');
    Route::post('/', [$controller, 'store'])->middleware('role:admin,super_admin');
    Route::put('/{id}', [$controller, 'update'])->whereNumber('id')->middleware('role:admin,super_admin');
    Route::delete('/{id}', [$controller, 'destroy'])->whereNumber('id')->middleware('role:admin,super_admin');
});
