<?php

use App\Http\Controllers\Api\IaController;
use App\Http\Controllers\PayrollPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('ia')->middleware('role:gestionnaire_ia')->group(function () {


    Route::get('payroll/pages/{slug}', [PayrollPageController::class, 'show']);
    Route::get('payroll/pages/{slug}/export', [PayrollPageController::class, 'export']);
    Route::get('payroll/payslips/{payslip}', [PayrollPageController::class, 'payslip'])
        ->middleware('permission:paie.bulletins.read');

    Route::get('dashboard', [IaController::class, 'dashboard']);

    Route::get('enseignants', [IaController::class, 'teachers'])
        ->middleware('permission:enseignants.read');
    Route::get('enseignants/{id}', [IaController::class, 'teacher'])
        ->whereNumber('id')
        ->middleware('permission:enseignants.read');
    Route::get('referentiels', [IaController::class, 'references'])
        ->middleware('permission:enseignants.read');

    Route::get('paie', [IaController::class, 'payroll'])
        ->middleware('permission:paie.bulletins.read');
    Route::get('paie/export', [IaController::class, 'payroll'])
        ->name('ia.payroll.export')
        ->middleware('permission:paie.bulletins.export');
    Route::get('paie/{id}', [IaController::class, 'payslip'])
        ->whereNumber('id')
        ->middleware('permission:paie.bulletins.read');

   
    Route::put('payroll/pages/{slug}', [PayrollPageController::class, 'update']);

    // Lignes d'une page (états de présence, cotisations, etc.)
    Route::post('payroll/pages/{slug}/lignes', [PayrollPageController::class, 'storeLine']);
    Route::put('payroll/pages/{slug}/lignes/{ligne}', [PayrollPageController::class, 'updateLine'])
        ->whereNumber('ligne');
    Route::delete('payroll/pages/{slug}/lignes/{ligne}', [PayrollPageController::class, 'destroyLine'])
        ->whereNumber('ligne');

    // Délégations de crédit
    Route::put('paie/delegations/{delegation}', [IaController::class, 'updateDelegation'])
        ->whereNumber('delegation')
        ->middleware('permission:paie.delegations.update');

    

    // Engagements
    Route::put('paie/engagements/{engagement}', [IaController::class, 'updateEngagement'])
        ->whereNumber('engagement')
        ->middleware('permission:paie.engagements.update');

           // Editions
Route::middleware('permission:paie.edition')->group(function () {
    Route::get('edition', [PayrollPageController::class, 'showEdition'])
        ->middleware('permission:paie.edition.read');

    Route::put('edition', [PayrollPageController::class, 'updateEdition'])
        ->middleware('permission:paie.edition.update');
});
});