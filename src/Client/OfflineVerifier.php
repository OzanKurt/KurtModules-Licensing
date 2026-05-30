<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Kurt\Modules\Licensing\Client\Data\OfflineResult;
use Kurt\Modules\Licensing\Crypto\ClaimsCodec;
use Kurt\Modules\Licensing\Crypto\Ed25519;

/**
 * Verifies a downloadable, Ed25519-signed license file entirely offline using
 * only the embedded public key. This is the air-gapped path: no network, no
 * server, no Core. A signature mismatch or a past `expires_at` claim fails it.
 */
final class OfflineVerifier
{
    public function __construct(private readonly string $publicKey) {}

    public function verifyBlob(string $blob): OfflineResult
    {
        $decoded = base64_decode($blob, true);

        if ($decoded === false) {
            return OfflineResult::invalid('malformed');
        }

        $data = json_decode($decoded, true);

        if (! is_array($data)) {
            return OfflineResult::invalid('malformed');
        }

        return $this->verify($data);
    }

    /**
     * @param  array<array-key, mixed>  $file
     */
    public function verify(array $file): OfflineResult
    {
        $claims = $file['claims'] ?? null;
        $signature = $file['signature'] ?? null;

        if (! is_array($claims) || ! is_string($signature)) {
            return OfflineResult::invalid('malformed');
        }

        if (! Ed25519::verify(ClaimsCodec::encode($claims), $signature, $this->publicKey)) {
            return OfflineResult::invalid('bad_signature');
        }

        $expiresAt = $claims['expires_at'] ?? null;

        if (is_string($expiresAt)) {
            $timestamp = strtotime($expiresAt);

            if ($timestamp !== false && $timestamp < time()) {
                return OfflineResult::invalid('expired');
            }
        }

        return OfflineResult::valid($claims);
    }
}
