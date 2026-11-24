<?php

use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\ConvocationsController;
use App\Http\Controllers\EtatPaieIndemnitesController;
use App\Http\Controllers\IndemnitesController;
use App\Http\Controllers\Paie\EtatSalaire\EtatSalaireController;
use App\Http\Controllers\PayrollActionController;
use App\Http\Controllers\PayrollPageController;
use App\Http\Controllers\PieceJustificativesController;
use App\Http\Controllers\TypeIndemnitesController;
use App\Http\Controllers\UserController;
use App\Services\PayrollPageService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DelegationCreditController;
use App\Http\Controllers\Api\DelegationVentilationController;


Route::middleware('auth:sanctum')->group(function () {
   

    Route::prefix('payroll')->group(function (): void {
        Route::middleware('payroll.access:read')->group(function (): void {
            // État des salaires : contrôleur dédié, déclaré avant les routes génériques {slug}.
            Route::get('/pages/paie-etat-salaires', [EtatSalaireController::class, 'index']);
            Route::get('/exports/paie-etat-salaires', [EtatSalaireController::class, 'download']);

            Route::get('/payslips/{payslip}', [PayrollPageController::class, 'payslip']);
            Route::get('/pages/{slug}', [PayrollPageController::class, 'show'])
                ->whereIn('slug', PayrollPageService::SLUGS);
            Route::get('/exports/{slug}', [PayrollPageController::class, 'export'])
                ->whereIn('slug', PayrollPageService::SLUGS);
        });

        Route::prefix('delegations-credits')->group(function (): void {

            /*
            |--------------------------------------------------------------------------
            | Lecture des délégations de crédits
            |--------------------------------------------------------------------------
            */
            Route::middleware('payroll.access:read')->group(function (): void {
        
                Route::get('/', [DelegationCreditController::class, 'index']);
        
                Route::get('/options', [DelegationCreditController::class, 'options']);
        
                Route::get('/{delegation}', [DelegationCreditController::class, 'show'])
                    ->whereNumber('delegation');
        
                /*
                |--------------------------------------------------------------------------
                | Lecture des ventilations
                |--------------------------------------------------------------------------
                */
                Route::get(
                    '/{delegation}/ventilations',
                    [DelegationVentilationController::class, 'index']
                )->whereNumber('delegation');
        
                Route::post(
                    '{delegation}/ventilations/{ventilation}/recuperer',
                    [DelegationVentilationController::class, 'recuperer']
                )->name('delegations-credits.ventilations.recuperer');

                Route::get(
                    '/{delegation}/ventilations/options',
                    [DelegationVentilationController::class, 'options']
                )->whereNumber('delegation');
        
                Route::get(
                    '/{delegation}/ventilations/{ventilation}',
                    [DelegationVentilationController::class, 'show']
                )->whereNumber('delegation')
                 ->whereNumber('ventilation');
            });
        
            /*
            |--------------------------------------------------------------------------
            | Création, modification et suppression des délégations
            |--------------------------------------------------------------------------
            */
            Route::middleware([
                'payroll.access:write',
                'throttle:payroll-write',
            ])->group(function (): void {
        
                Route::post('/', [DelegationCreditController::class, 'store']);
        
                Route::put(
                    '/{delegation}',
                    [DelegationCreditController::class, 'update']
                )->whereNumber('delegation');
        
                Route::delete(
                    '/{delegation}',
                    [DelegationCreditController::class, 'destroy']
                )->whereNumber('delegation');
        
                /*
                |--------------------------------------------------------------------------
                | Création, modification et suppression des ventilations
                |--------------------------------------------------------------------------
                */
                Route::post(
                    '/{delegation}/ventilations',
                    [DelegationVentilationController::class, 'store']
                )->whereNumber('delegation');
        
                Route::put(
                    '/{delegation}/ventilations/{ventilation}',
                    [DelegationVentilationController::class, 'update']
                )->whereNumber('delegation')
                 ->whereNumber('ventilation');
        
                Route::delete(
                    '/{delegation}/ventilations/{ventilation}',
                    [DelegationVentilationController::class, 'destroy']
                )->whereNumber('delegation')
                 ->whereNumber('ventilation');
            });
        });



        Route::post('/actions/close-period', [PayrollActionController::class, 'handle'])
            ->defaults('action', 'close-period')
            ->middleware(['payroll.access:close', 'throttle:payroll-close']);

        Route::post('/actions/{action}', [PayrollActionController::class, 'handle'])
            ->whereIn('action', [
                'configure-teacher-payroll',
                'save-attendance',
                'add-element',
                'apply-tabaski-advance',
                'apply-tabaski-deduction',
                'exempt-element',
                'validate-attendance',
                'validate-elements',
                'calculate-payroll',
                'open-period',
                'validate-payroll',
                'mark-paid',
            ])
            ->middleware(['payroll.access:write', 'throttle:payroll-write']);
    });
});
