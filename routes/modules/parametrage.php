<?php

use App\Http\Controllers\Api\Parametrage\CategorieController;

use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use Illuminate\Support\Facades\Route;

Route::post('lieux-service', [LieuServiceController::class, 'store'])
    ->middleware('permission:parametrage.lieux_service.manage');

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
