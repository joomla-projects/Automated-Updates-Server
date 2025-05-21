<?php

use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:site'])->group(function () {
    Route::controller(SiteController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('check', 'check');
        Route::post('delete', 'delete');
    });
});
