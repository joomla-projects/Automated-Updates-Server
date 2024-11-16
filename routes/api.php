<?php

use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::controller(SiteController::class)->group(function () {
        Route::get('register', 'register');
        Route::get('check', 'check');
        Route::delete('delete', 'delete');
    });
});
