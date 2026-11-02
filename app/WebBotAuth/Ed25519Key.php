<?php

namespace App\WebBotAuth;

/**
 * Ed25519 key pair used for Web Bot Auth (HTTP Message Signatures, RFC 9421)
 */
class Ed25519Key
{
    /**
     * @var non-empty-string
     */
    private readonly string $keyPair;

    private function __construct(private readonly string $seed)
    {
        if (strlen($seed) !== SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            throw new \InvalidArgumentException('Invalid Ed25519 seed length');
        }

        $this->keyPair = sodium_crypto_sign_seed_keypair($seed);
    }

    public static function generate(): self
    {
        return new self(random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES));
    }

    public static function fromSeed(string $seed): self
    {
        return new self($seed);
    }

    /**
     * Load a private key from a JWK (RFC 8037) JSON string
     */
    public static function fromJwk(string $json): self
    {
        $jwk = json_decode($json, true);

        if (
            !is_array($jwk)
            || ($jwk['kty'] ?? null) !== 'OKP'
            || ($jwk['crv'] ?? null) !== 'Ed25519'
            || !is_string($jwk['d'] ?? null)
        ) {
            throw new \InvalidArgumentException('Key is not a private Ed25519 JWK');
        }

        $key = new self(sodium_base642bin($jwk['d'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING));

        if (isset($jwk['x']) && $jwk['x'] !== $key->publicJwk()['x']) {
            throw new \InvalidArgumentException('Public part of the JWK does not match the private key');
        }

        return $key;
    }

    public static function fromJwkFile(string $path): self
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException('Unable to read Web Bot Auth key file: ' . $path);
        }

        return self::fromJwk($content);
    }

    public function publicKey(): string
    {
        return sodium_crypto_sign_publickey($this->keyPair);
    }

    /**
     * Public JWK - safe to publish
     *
     * @return array{kty: string, crv: string, x: string}
     */
    public function publicJwk(): array
    {
        return [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => sodium_bin2base64($this->publicKey(), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
        ];
    }

    /**
     * Private JWK including the secret "d" parameter - never publish this
     *
     * @return array{kty: string, crv: string, x: string, d: string}
     */
    public function privateJwk(): array
    {
        return $this->publicJwk() + ['d' => sodium_bin2base64($this->seed, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING)];
    }

    /**
     * JWK SHA-256 thumbprint (RFC 7638 / RFC 8037 A.3), used as keyid
     */
    public function thumbprint(): string
    {
        $jwk = $this->publicJwk();

        // Required members in lexicographic order, no whitespace
        $canonical = json_encode(
            ['crv' => $jwk['crv'], 'kty' => $jwk['kty'], 'x' => $jwk['x']],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return sodium_bin2base64(hash('sha256', $canonical, true), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    public function sign(string $message): string
    {
        return sodium_crypto_sign_detached($message, sodium_crypto_sign_secretkey($this->keyPair));
    }
}
