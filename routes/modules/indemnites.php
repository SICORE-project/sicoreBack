<?php

use App\Http\Controllers\Api\Indemnites\NotificationController;
use Illuminate\Support\Facades\Route;


Route::prefix('indemnites')->group(function () {




    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/', [NotificationController::class, 'store']);
    });
});
