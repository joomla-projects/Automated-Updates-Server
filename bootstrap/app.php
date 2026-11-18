<?php

use GuzzleHttp\Exception\ResponseException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'horizonBasicAuth' => \App\Http\Middleware\HorizonBasicAuthMiddleware::class
        ]);

        $middleware->web(remove: [
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (ResponseException $e) {
            Log::warning('HTTP Request Exception', [
                'url' => (string) $e->getRequest()->getUri(),
                'status' => $e->getResponse()->getStatusCode(),
                'body' => substr((string) $e->getResponse()->getBody(), 0, 250)
            ]);
        })->stop();

        $exceptions->report(function (\GuzzleHttp\Exception\ConnectException $e) {
            Log::warning('Connect Exception', [
                'message' => $e->getMessage(),
                'url' => (string) $e->getRequest()?->getUri()
            ]);
        })->stop();

        // We have jobs that will run into max attempts, don't write them to log
        $exceptions->dontReport(\Illuminate\Queue\MaxAttemptsExceededException::class);
    })->create();
