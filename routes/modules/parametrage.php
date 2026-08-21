<?php
use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Http\Controllers\Api\Parametrage\CategorieController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;

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