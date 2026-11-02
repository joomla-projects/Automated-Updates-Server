<?php

namespace Tests\Unit\WebBotAuth;

use App\WebBotAuth\Ed25519Key;
use App\WebBotAuth\WebBotAuth;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Tests\TestCase;

class WebBotAuthTest extends TestCase
{
    // test-key-ed25519 from RFC 9421 B.1.4, also used in the Web Bot Auth examples
    private const TEST_KEY_D = 'n4Ni-HpISpVObnQMW0wOhCKROaIKqKtW_2ZYb2p9KcU';
    private const TEST_KEY_X = 'JrQLj5P_89iXES9-vFgrIy29clF9CC_oPPsw3c5D0bs';
    private const TEST_KEY_ID = 'poqkLGiymh_W0uP6PZFw-dvez3QJT5SolqXBCW38r0U';

    private function testKey(): Ed25519Key
    {
        return Ed25519Key::fromSeed(sodium_base642bin(self::TEST_KEY_D, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING));
    }

    public function testPublicKeyAndThumbprintMatchReferenceKey(): void
    {
        $key = $this->testKey();

        $this->assertSame(self::TEST_KEY_X, $key->publicJwk()['x']);
        $this->assertSame(self::TEST_KEY_ID, $key->thumbprint());
    }

    public function testJwkRoundtrip(): void
    {
        $key = Ed25519Key::generate();
        $loaded = Ed25519Key::fromJwk((string) json_encode($key->privateJwk()));

        $this->assertSame($key->thumbprint(), $loaded->thumbprint());
        $this->assertArrayNotHasKey('d', $key->publicJwk());
    }

    public function testJwkWithMismatchingPublicKeyIsRejected(): void
    {
        $jwk = Ed25519Key::generate()->privateJwk();
        $jwk['x'] = self::TEST_KEY_X;

        $this->expectException(\InvalidArgumentException::class);

        Ed25519Key::fromJwk((string) json_encode($jwk));
    }

    /**
     * Reproduces the example from the Cloudflare docs / draft-meunier-web-bot-auth-architecture byte by byte
     */
    public function testRequestSignatureMatchesReferenceExample(): void
    {
        $webBotAuth = new WebBotAuth($this->testKey(), 'https://signature-agent.test', 3600);

        $request = $webBotAuth->signRequest(
            new Request('GET', 'https://example.com/'),
            1735689600,
            'e8N7S2MFd/qrd6T2R3tdfAuuANngKI7LFtKYI/vowzk4lAZYadIX6wW25MwG7DCT9RUKAJ0qVkU0mEeLElW1qg=='
        );

        $this->assertSame('"https://signature-agent.test"', $request->getHeaderLine('Signature-Agent'));
        $this->assertSame(
            'sig1=("@authority" "signature-agent");created=1735689600'
            . ';keyid="poqkLGiymh_W0uP6PZFw-dvez3QJT5SolqXBCW38r0U";alg="ed25519";expires=1735693200'
            . ';nonce="e8N7S2MFd/qrd6T2R3tdfAuuANngKI7LFtKYI/vowzk4lAZYadIX6wW25MwG7DCT9RUKAJ0qVkU0mEeLElW1qg=="'
            . ';tag="web-bot-auth"',
            $request->getHeaderLine('Signature-Input')
        );
        $this->assertSame(
            'sig1=:jdq0SqOwHdyHr9+r5jw3iYZH6aNGKijYp/EstF4RQTQdi5N5YYKrD+mCT1HA1nZDsi6nJKuHxUi/5Syp3rLWBA==:',
            $request->getHeaderLine('Signature')
        );
    }

    public function testDirectorySignatureVerifies(): void
    {
        $key = $this->testKey();
        $webBotAuth = new WebBotAuth($key, 'https://Signature-Agent.test/');

        $headers = $webBotAuth->signDirectoryResponse(1000, 'nonce');

        $this->assertStringStartsWith('sig1=("@authority";req);created=1000;', $headers['Signature-Input']);
        $this->assertStringContainsString(';expires=4600;', $headers['Signature-Input']);
        $this->assertStringEndsWith(';tag="http-message-signatures-directory"', $headers['Signature-Input']);

        $params = substr($headers['Signature-Input'], strlen('sig1='));
        $base = "\"@authority\";req: signature-agent.test\n\"@signature-params\": " . $params;
        $signature = base64_decode(substr($headers['Signature'], strlen('sig1=:'), -1));

        $this->assertTrue(sodium_crypto_sign_verify_detached($signature, $base, $key->publicKey()));
    }

    public function testFromConfigLoadsKeyFile(): void
    {
        $keyPath = (string) tempnam(sys_get_temp_dir(), 'wba');
        file_put_contents($keyPath, json_encode($this->testKey()->privateJwk()));

        try {
            $webBotAuth = WebBotAuth::fromConfig([
                'key_path' => $keyPath,
                'signature_agent' => 'https://signature-agent.test/',
                'validity' => 60,
            ]);
        } finally {
            unlink($keyPath);
        }

        $this->assertSame(self::TEST_KEY_ID, $webBotAuth->key->thumbprint());
        $this->assertSame('https://signature-agent.test', $webBotAuth->signatureAgent);
    }

    public function testSignatureAgentMustUseHttps(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WebBotAuth($this->testKey(), 'http://signature-agent.test');
    }

    public function testAuthorityHandlesPorts(): void
    {
        $this->assertSame('example.com', WebBotAuth::authority(new Uri('https://Example.com:443/foo')));
        $this->assertSame('example.com', WebBotAuth::authority(new Uri('http://example.com:80/')));
        $this->assertSame('example.com:8443', WebBotAuth::authority(new Uri('https://example.com:8443/')));
    }

    public function testMiddlewareSignsRedirectedRequestsForTheirOwnAuthority(): void
    {
        $webBotAuth = new WebBotAuth($this->testKey(), 'https://signature-agent.test');
        $history = [];

        $stack = HandlerStack::create(new MockHandler([
            new Response(302, ['Location' => 'https://other.test:8443/target']),
            new Response(200),
        ]));
        $stack->after('allow_redirects', $webBotAuth, 'web_bot_auth');
        $stack->push(Middleware::history($history));

        (new Client(['handler' => $stack]))->get('https://example.com/');

        $this->assertCount(2, $history);

        foreach ($history as $transaction) {
            $request = $transaction['request'];
            $authority = WebBotAuth::authority($request->getUri());
            $params = substr($request->getHeaderLine('Signature-Input'), strlen('sig1='));
            $base = "\"@authority\": " . $authority
                . "\n\"signature-agent\": " . $request->getHeaderLine('Signature-Agent')
                . "\n\"@signature-params\": " . $params;
            $signature = base64_decode(substr($request->getHeaderLine('Signature'), strlen('sig1=:'), -1));

            $this->assertTrue(
                sodium_crypto_sign_verify_detached($signature, $base, $this->testKey()->publicKey()),
                'Invalid signature for ' . $authority
            );
        }
    }
}
