<?php

namespace App\Providers;

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
            $siteIp = 'default';

            if (is_string($request->input('url'))) {
                $siteHost = parse_url($request->input('url'), PHP_URL_HOST);
            }

            if ($siteHost !== 'default' && $dnsResult = dns_get_record((string) $siteHost, DNS_A)) {
                $siteIp = $dnsResult[0]['ip'];
            }

            return [
                Limit::perMinute(5)->by("sitehost-" . $siteHost),
                Limit::perMinute(10)->by("siteip-" . $siteIp),
                Limit::perMinute(50)->by("ip-" . $request->ip())
            ];
        });
    }
}
