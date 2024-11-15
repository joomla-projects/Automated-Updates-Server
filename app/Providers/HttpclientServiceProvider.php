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
            $handlerStack = HandlerStack::create(new CurlHandler());
            $handlerStack->push(
                Middleware::retry(
                    function (
                        $retries,
                        Request $request,
                        Response $response = null,
                        \Throwable $exception = null
                    ) {
                        // Limit the number of retries to 3
                        if ($retries >= 3) {
                            return false;
                        }

                        // Retry connection exceptions
                        if ($exception instanceof ConnectException) {
                            return true;
                        }

                        if ($response) {
                            // Retry on server errors
                            if ($response->getStatusCode() >= 500) {
                                return true;
                            }
                        }

                        return false;
                    },
                    function ($numberOfRetries) {
                        return 1000 * $numberOfRetries;
                    }
                )
            );

            return new Client([
                'handler'  => $handlerStack,
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
