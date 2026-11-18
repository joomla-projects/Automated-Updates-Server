<?php

namespace Tests\Feature\WebBotAuth;

use App\WebBotAuth\Ed25519Key;
use App\WebBotAuth\WebBotAuth;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    public function testDirectoryIsNotFoundWhenDisabled(): void
    {
        $this->assertFalse($this->app->bound(WebBotAuth::class));

        $this->get('/.well-known/http-message-signatures-directory')->assertNotFound();
    }

    public function testDirectoryPublishesOnlyThePublicKey(): void
    {
        $key = Ed25519Key::generate();
        $this->app->instance(WebBotAuth::class, new WebBotAuth($key, 'https://updates.example.org'));

        $response = $this->get('/.well-known/http-message-signatures-directory');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/http-message-signatures-directory+json');
        $response->assertExactJson(['keys' => [$key->publicJwk() + ['kid' => $key->thumbprint()]]]);
        $this->assertStringNotContainsString('"d"', (string) $response->getContent());

        $input = (string) $response->headers->get('Signature-Input');
        $this->assertStringContainsString('keyid="' . $key->thumbprint() . '"', $input);

        $base = "\"@authority\";req: updates.example.org\n\"@signature-params\": " . substr($input, strlen('sig1='));
        $signature = base64_decode(substr((string) $response->headers->get('Signature'), strlen('sig1=:'), -1));

        $this->assertTrue(sodium_crypto_sign_verify_detached($signature, $base, $key->publicKey()));
    }
}
