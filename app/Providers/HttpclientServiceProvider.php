<?php

namespace App\Providers;

use App\WebBotAuth\WebBotAuth;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
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
        // Web Bot Auth is only available if it's enabled
        if (config()->boolean('web_bot_auth.enabled')) {
            $this->app->singleton(WebBotAuth::class, function ($app) {
                return WebBotAuth::fromConfig($app['config']->get('web_bot_auth'));
            });
        }

        $this->app->singleton(Client::class, function ($app) {
            $stack = HandlerStack::create();

            if ($app->bound(WebBotAuth::class)) {
                // Placed after the redirect middleware, so every redirected request is signed for its own authority
                $stack->after('allow_redirects', $app->make(WebBotAuth::class), 'web_bot_auth');
            }

            return new Client([
                'handler' => $stack,
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
