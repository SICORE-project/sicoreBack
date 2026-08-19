<?php
use App\Http\Controllers\Api\Parametrage\CorpsController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Api\Parametrage\SyndicatController;


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
