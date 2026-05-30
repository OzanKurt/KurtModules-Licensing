<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Crypto;

use InvalidArgumentException;
use SodiumException;

/**
 * Thin libsodium Ed25519 wrapper shared by the server (signs license files) and
 * the client SDK (verifies them). Framework-free — no Laravel, no Core — so it
 * is safe to embed inside a premium package via the client SDK.
 */
final class Ed25519
{
    /**
     * Generate a base64-encoded keypair. The secret stays on the server; the
     * public key is embedded in each premium package to verify signed files.
     *
     * @return array{public: string, secret: string}
     */
    public static function generateKeyPair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    public static function sign(string $message, string $secretKeyBase64): string
    {
        $secret = base64_decode($secretKeyBase64, true);

        if ($secret === false || $secret === '') {
            throw new InvalidArgumentException('Invalid base64 secret key.');
        }

        return base64_encode(sodium_crypto_sign_detached($message, $secret));
    }

    public static function verify(string $message, string $signatureBase64, string $publicKeyBase64): bool
    {
        $signature = base64_decode($signatureBase64, true);
        $public = base64_decode($publicKeyBase64, true);

        if ($signature === false || $signature === '' || $public === false || $public === '') {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached($signature, $message, $public);
        } catch (SodiumException) {
            return false;
        }
    }
}
