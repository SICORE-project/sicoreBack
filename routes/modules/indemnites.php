<?php

declare(strict_types=1);

use App\Http\Controllers\api\indemnites\AccuseReceptionController;
use Illuminate\Support\Facades\Route;

Route::get(
        'accuses-reception/current-agent',
        [AccuseReceptionController::class, 'currentAgent']
    );

Route::get(
        'accuses-reception/agents/{agent}',
        [AccuseReceptionController::class, 'agent']
    );


//ute::apiResource('accuses-reception', AccuseReceptionController::class);

Route::apiResource('accuses-reception', AccuseReceptionController::class)
    ->parameters([
        'accuses-reception' => 'accuseReception',
    ]);



