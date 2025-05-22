<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\ServiceProvider;

class HttpclientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                'allow_redirects' => [
                    'max'             => 5,
                    'strict'          => true,  // "strict" redirects - that's key as a redirected POST stays a POST
                    'referer'         => true,
                    'track_redirects' => true
                ],
                'headers'         => [
                    'User-Agent' => 'Joomla.org Automated Updates Server',
                    'Accept-Encoding' => 'gzip, deflate'
                ],
                'verify' => false
            ]);
        });
    }
}
