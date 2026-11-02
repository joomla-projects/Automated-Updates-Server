<?php

namespace App\WebBotAuth;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Web Bot Auth: signs requests with HTTP Message Signatures (RFC 9421), usable as Guzzle middleware
 *
 * @see https://developers.cloudflare.com/bots/reference/bot-verification/web-bot-auth/
 */
class WebBotAuth
{
    public const LABEL = 'sig1';

    public const TAG_REQUEST = 'web-bot-auth';

    public const TAG_DIRECTORY = 'http-message-signatures-directory';

    public readonly string $signatureAgent;

    public function __construct(
        public readonly Ed25519Key $key,
        string $signatureAgent,
        private readonly int $validity = 60
    ) {
        if (!str_starts_with($signatureAgent, 'https://')) {
            throw new \InvalidArgumentException('Web Bot Auth requires an https:// signature agent URL');
        }

        $this->signatureAgent = rtrim($signatureAgent, '/');
    }

    /**
     * @param   array{key_path: string, signature_agent: string, validity: int}  $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            Ed25519Key::fromJwkFile($config['key_path']),
            $config['signature_agent'],
            $config['validity']
        );
    }

    /**
     * Guzzle middleware; push it after "allow_redirects", so redirected requests are signed for their own authority
     */
    public function __invoke(callable $handler): callable
    {
        return fn (RequestInterface $request, array $options) => $handler($this->signRequest($request), $options);
    }

    /**
     * Add Signature-Agent, Signature-Input and Signature headers to an outgoing request
     */
    public function signRequest(RequestInterface $request, ?int $created = null, ?string $nonce = null): RequestInterface
    {
        // Must be a quoted sf-string, Cloudflare rejects the dictionary form
        $signatureAgent = '"' . $this->signatureAgent . '"';

        $signature = $this->createSignature(
            [
                '"@authority"' => self::authority($request->getUri()),
                '"signature-agent"' => $signatureAgent,
            ],
            self::TAG_REQUEST,
            $this->validity,
            $created,
            $nonce
        );

        return $request
            ->withHeader('Signature-Agent', $signatureAgent)
            ->withHeader('Signature-Input', $signature['input'])
            ->withHeader('Signature', $signature['signature']);
    }

    /**
     * Signature headers for the key directory response, signed for the authority of the signature agent
     *
     * @return array{Signature-Input: string, Signature: string}
     */
    public function signDirectoryResponse(?int $created = null, ?string $nonce = null): array
    {
        $signature = $this->createSignature(
            ['"@authority";req' => self::authority(new Uri($this->signatureAgent))],
            self::TAG_DIRECTORY,
            3600,
            $created,
            $nonce
        );

        return [
            'Signature-Input' => $signature['input'],
            'Signature' => $signature['signature'],
        ];
    }

    /**
     * "@authority" derived component: lowercase host plus port if not the scheme default (RFC 9421 2.2.3)
     */
    public static function authority(UriInterface $uri): string
    {
        $authority = strtolower($uri->getHost());
        $port = $uri->getPort();

        $defaultPort = ['http' => 80, 'https' => 443][strtolower($uri->getScheme())] ?? null;

        if ($port !== null && $port !== $defaultPort) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    /**
     * @param   array<string, string>  $components  Serialized component identifier => value
     *
     * @return array{input: string, signature: string}
     */
    private function createSignature(
        array $components,
        string $tag,
        int $validity,
        ?int $created,
        ?string $nonce
    ): array {
        $created ??= time();
        $nonce ??= base64_encode(random_bytes(64));

        // Same parameter order as the reference examples in the Web Bot Auth drafts
        $params = '(' . implode(' ', array_keys($components)) . ')'
            . ';created=' . $created
            . ';keyid="' . $this->key->thumbprint() . '"'
            . ';alg="ed25519"'
            . ';expires=' . ($created + $validity)
            . ';nonce="' . $nonce . '"'
            . ';tag="' . $tag . '"';

        $lines = [];

        foreach ($components as $identifier => $value) {
            $lines[] = $identifier . ': ' . $value;
        }

        $lines[] = '"@signature-params": ' . $params;

        return [
            'input' => self::LABEL . '=' . $params,
            'signature' => self::LABEL . '=:' . base64_encode($this->key->sign(implode("\n", $lines))) . ':',
        ];
    }
}
