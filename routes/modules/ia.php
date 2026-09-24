<?php

use App\Http\Controllers\Api\IaController;
use Illuminate\Support\Facades\Route;

Route::prefix('ia')->middleware('role:gestionnaire_ia')->group(function () {
    Route::get('payroll/pages/{slug}', [\App\Http\Controllers\PayrollPageController::class, 'show']);
    Route::get('payroll/pages/{slug}/export', [\App\Http\Controllers\PayrollPageController::class, 'export']);
    Route::get('payroll/payslips/{payslip}', [\App\Http\Controllers\PayrollPageController::class, 'payslip'])->middleware('permission:paie.bulletins.read');
    Route::get('dashboard',[IaController::class,'dashboard']);
    Route::get('enseignants',[IaController::class,'teachers'])->middleware('permission:enseignants.read');
    Route::get('enseignants/{id}',[IaController::class,'teacher'])->whereNumber('id')->middleware('permission:enseignants.read');
    Route::get('referentiels',[IaController::class,'references'])->middleware('permission:enseignants.read');
    Route::get('paie',[IaController::class,'payroll'])->middleware('permission:paie.bulletins.read');
    Route::get('paie/export',[IaController::class,'payroll'])->name('ia.payroll.export')->middleware('permission:paie.bulletins.export');
    Route::get('paie/{id}',[IaController::class,'payslip'])->whereNumber('id')->middleware('permission:paie.bulletins.read');
});
