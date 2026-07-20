<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Kurt\Modules\Licensing\Client\Data\OfflineResult;
use Kurt\Modules\Licensing\Crypto\ClaimsCodec;
use Kurt\Modules\Licensing\Crypto\Ed25519;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;

/**
 * Verifies a downloadable, Ed25519-signed license file entirely offline using
 * only the embedded public key. This is the air-gapped path: no network, no
 * server, no Core.
 *
 * A file fails if: its `format` tag is missing or does not match the signer
 * (unknown/incompatible layout), the signature does not match, its `expires_at`
 * has passed, or its `not_after` re-issue window has elapsed. The last is the
 * offline withdrawal path: entitlement is revoked by simply not re-signing a
 * fresh file. `$skewSeconds` of leeway absorbs a slightly-off local clock so
 * time comparisons do not fail spuriously.
 */
final class OfflineVerifier
{
    public function __construct(
        private readonly string $publicKey,
        private readonly int $skewSeconds = 0,
    ) {}

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
        $format = $file['format'] ?? null;

        if (! is_array($claims) || ! is_string($signature)) {
            return OfflineResult::invalid('malformed');
        }

        // Reject anything we do not know how to interpret before trusting its
        // claims. `format` is metadata (outside the signed payload), so this is
        // a compatibility gate, not a security one — the signature check below
        // is what protects the claims.
        if (! is_string($format) || $format !== LicenseFileSigner::FORMAT) {
            return OfflineResult::invalid('unsupported_format');
        }

        if (! Ed25519::verify(ClaimsCodec::encode($claims), $signature, $this->publicKey)) {
            return OfflineResult::invalid('bad_signature');
        }

        if ($this->hasPassed($claims['expires_at'] ?? null)) {
            return OfflineResult::invalid('expired');
        }

        // `not_after` is mandatory: a file without a valid, unelapsed re-issue
        // window cannot be trusted offline, since that window is the only lever
        // for withdrawing entitlement without a server call.
        $notAfter = $claims['not_after'] ?? null;

        if (! is_string($notAfter) || strtotime($notAfter) === false || $this->hasPassed($notAfter)) {
            return OfflineResult::invalid('stale');
        }

        return OfflineResult::valid($claims);
    }

    /**
     * True when an ISO-8601 timestamp lies in the past, allowing `$skewSeconds`
     * of clock leeway. A non-string or unparseable value is treated as not
     * passed so callers can enforce presence separately.
     */
    private function hasPassed(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $timestamp = strtotime($value);

        return $timestamp !== false && $timestamp < time() - $this->skewSeconds;
    }
}
