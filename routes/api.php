<?php

use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::controller(SiteController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('check', 'check');
        Route::delete('delete', 'delete');
    });
});
