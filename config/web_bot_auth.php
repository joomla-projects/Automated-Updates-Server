<?php

// Web Bot Auth, see https://developers.cloudflare.com/bots/reference/bot-verification/web-bot-auth/
return [
    'enabled' => (bool) env('WEB_BOT_AUTH_ENABLED', false),
    // Private Ed25519 key as JWK, create it with "php artisan web-bot-auth:generate-key"
    'key_path' => env('WEB_BOT_AUTH_KEY_PATH', storage_path('web-bot-auth.key')),
    // Public https origin serving /.well-known/http-message-signatures-directory
    'signature_agent' => env('WEB_BOT_AUTH_SIGNATURE_AGENT', env('APP_URL')),
    // Signature lifetime in seconds
    'validity' => (int) env('WEB_BOT_AUTH_VALIDITY', 60),
];
