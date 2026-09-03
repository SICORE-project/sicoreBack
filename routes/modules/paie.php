<?php

use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\ConvocationsController;
use App\Http\Controllers\EtatPaieIndemnitesController;
use App\Http\Controllers\IndemnitesController;
use App\Http\Controllers\PayrollActionController;
use App\Http\Controllers\PayrollPageController;
use App\Http\Controllers\PieceJustificativesController;
use App\Http\Controllers\TypeIndemnitesController;
use App\Http\Controllers\UserController;
use App\Services\PayrollPageService;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
   

    Route::prefix('payroll')->group(function (): void {
        Route::middleware('payroll.access:read')->group(function (): void {
            Route::get('/payslips/{payslip}', [PayrollPageController::class, 'payslip']);
            Route::get('/pages/{slug}', [PayrollPageController::class, 'show'])
                ->whereIn('slug', PayrollPageService::SLUGS);
            Route::get('/exports/{slug}', [PayrollPageController::class, 'export'])
                ->whereIn('slug', PayrollPageService::SLUGS);
        });

        Route::post('/actions/close-period', [PayrollActionController::class, 'handle'])
            ->defaults('action', 'close-period')
            ->middleware(['payroll.access:close', 'throttle:payroll-close']);

        Route::post('/actions/{action}', [PayrollActionController::class, 'handle'])
            ->whereIn('action', [
                'configure-teacher-payroll',
                'create-period',
                'save-attendance',
                'add-element',
                'apply-tabaski-advance',
                'apply-tabaski-deduction',
                'exempt-element',
                'validate-attendance',
                'validate-elements',
                'calculate-payroll',
                'validate-payroll',
                'mark-paid',
            ])
            ->middleware(['payroll.access:write', 'throttle:payroll-write']);
    });
});