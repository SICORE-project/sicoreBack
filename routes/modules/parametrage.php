<?php

use App\Http\Controllers\Api\Parametrage\CompteBancaireEnseignantController;
use App\Http\Controllers\Api\Parametrage\InstitutFinancierController;
use Illuminate\Support\Facades\Route;

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
