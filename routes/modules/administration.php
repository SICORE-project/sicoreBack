<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;


Route::middleware([
    'permission:administration.users.read'
])
->group(function(){

    Route::apiResource(
        'users',
        UserController::class
    );

});
