<?php

namespace App\Http\Controllers;

use App\WebBotAuth\WebBotAuth;
use Illuminate\Http\JsonResponse;

/**
 * Serves the signed Web Bot Auth key directory at /.well-known/http-message-signatures-directory
 */
class WebBotAuthDirectoryController
{
    public function __invoke(): JsonResponse
    {
        abort_unless(app()->bound(WebBotAuth::class), 404);

        $webBotAuth = app(WebBotAuth::class);

        return new JsonResponse(
            ['keys' => [$webBotAuth->key->publicJwk() + ['kid' => $webBotAuth->key->thumbprint()]]],
            200,
            [
                'Content-Type' => 'application/http-message-signatures-directory+json',
                'Cache-Control' => 'max-age=300',
                ...$webBotAuth->signDirectoryResponse(),
            ],
            JSON_UNESCAPED_SLASHES
        );
    }
}
