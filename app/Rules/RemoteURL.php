<?php

namespace App\Rules;

use App\Network\DNSLookup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;

class RemoteURL implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("Invalid URL: URL must be a string.");
        }

        $host = parse_url($value, PHP_URL_HOST);
        $ips = App::make(DNSLookup::class)->getIPs($host);

        // Could not resolve given address
        if (count($ips) === 0) {
            $fail("Invalid URL: unresolvable site URL.");
        }

        // Check each resolved IP
        foreach ($ips as $ip) {
            if (!filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
            )
            ) {
                $fail("Invalid URL: local address are disallowed as site URL.");
            }
        }
    }
}
