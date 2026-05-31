<?php

namespace App\Rules;

use App\Network\NetworkHelper;
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

            return;
        }

        $host = (string) parse_url($value, PHP_URL_HOST);
        /** @var NetworkHelper $networkHelper */
        $networkHelper = App::make(NetworkHelper::class);

        // Check IPs
        if (!$networkHelper->isValidRemoteHost($host)) {
            $fail("Invalid URL: please provide a valid, resolvable Host that does not resolve to local IPs.");
        }
    }
}
