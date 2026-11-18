<?php

use App\Http\Controllers\WebBotAuthDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
});

Route::get('/.well-known/http-message-signatures-directory', WebBotAuthDirectoryController::class);
