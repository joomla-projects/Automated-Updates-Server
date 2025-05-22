<?php

namespace App\Providers;

use App\Network\DNSLookup;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('site', function (Request $request) {
            $siteHost = 'default';

            if (is_string($request->input('url'))) {
                $siteHost = (string) parse_url($request->input('url'), PHP_URL_HOST);
            }

            // Define a rate limit per target IP
            $siteIpLimits = [];

            if ($siteHost !== 'default') {
                $siteIps = (new DNSLookup())->getIPs($siteHost);

                foreach ($siteIps as $siteIp) {
                    $siteIpLimits[] = Limit::perMinute(5)->by("siteip-" . $siteIp);
                }
            }

            if (!count($siteIpLimits)) {
                $siteIpLimits = [Limit::perMinute(5)->by("siteip-default")];
            }

            return [
                Limit::perMinute(5)->by("sitehost-" . $siteHost),
                Limit::perMinute(50)->by("requestip-" . $request->ip()),
                ...$siteIpLimits
            ];
        });
    }
}
