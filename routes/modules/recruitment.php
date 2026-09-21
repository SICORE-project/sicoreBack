<?php

use App\Http\Controllers\Api\RecruitmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment')->group(function () {
    Route::get('establishments', [RecruitmentController::class, 'establishments'])->middleware('permission:recruitment.service');
    Route::get('template', [RecruitmentController::class, 'template'])->middleware('permission:recruitment.import');
    Route::get('batches', [RecruitmentController::class, 'index'])->middleware('permission:recruitment.read');
    Route::post('batches', [RecruitmentController::class, 'import'])->middleware('permission:recruitment.import');
    Route::get('batches/{id}', [RecruitmentController::class, 'show'])->whereNumber('id')->middleware('permission:recruitment.read');
    Route::post('batches/{id}/os', [RecruitmentController::class, 'os'])->whereNumber('id')->middleware('permission:recruitment.import');
    Route::post('batches/{id}/transmit', [RecruitmentController::class, 'transmit'])->whereNumber('id')->middleware('permission:recruitment.transmit');
    Route::post('members/{member}/service', [RecruitmentController::class, 'service'])->whereNumber('member')->middleware('permission:recruitment.service');
    Route::post('members/{member}/transition', [RecruitmentController::class, 'transition'])->whereNumber('member')->middleware('permission:recruitment.transition');
    Route::get('documents/{event}', [RecruitmentController::class, 'document'])->whereNumber('event')->middleware('permission:recruitment.read');
    Route::get('notices', [RecruitmentController::class, 'notices'])->middleware('permission:recruitment.read');
    Route::post('notices/{id}/read', [RecruitmentController::class, 'readNotice'])->whereNumber('id')->middleware('permission:recruitment.read');
});


