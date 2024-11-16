<?php

use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::controller(SiteController::class)->group(function () {
        Route::get('register', 'register');
        Route::get('check/{hase}', 'check');
        Route::delete('delete/{hash}', 'delete');
    });
});

