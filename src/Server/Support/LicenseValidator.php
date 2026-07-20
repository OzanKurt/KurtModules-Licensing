<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Server\Data\ValidationResult;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Online validation: resolves a key to a license and reports whether it may
 * run right now, optionally bound to a known activation fingerprint. Every
 * outcome (pass or fail) is audited and a successful fingerprinted check
 * refreshes the seat heartbeat.
 */
final class LicenseValidator
{
    public function __construct(
        private readonly KeyHasher $hasher,
        private readonly ActivationManager $activations,
        private readonly EventLogger $events,
    ) {}

    public function validateKey(string $key, ?string $fingerprint = null): ValidationResult
    {
        $license = License::query()->where('key_hash', $this->hasher->hash($key))->first();

        if ($license === null) {
            return ValidationResult::invalid('not_found');
        }

        return $this->validate($license, $fingerprint);
    }

    public function validate(License $license, ?string $fingerprint = null): ValidationResult
    {
        if ($license->status === LicenseStatus::Revoked) {
            return $this->fail($license, 'revoked');
        }

        if ($license->status === LicenseStatus::Suspended) {
            return $this->fail($license, 'suspended');
        }

        if ($license->hasExpired()) {
            return $this->fail($license, 'expired');
        }

        if (! $license->status->isUsable()) {
            return $this->fail($license, 'inactive');
        }

        if ($fingerprint !== null) {
            $seat = $license->activations()
                ->whereIn('fingerprint_hash', $this->activations->fingerprintHashes($fingerprint))
                ->whereNull('deactivated_at');

            if (! $seat->exists()) {
                return $this->fail($license, 'fingerprint_unknown');
            }

            $seat->update(['last_seen_at' => now()]);
        }

        $this->events->log($license, LicenseEventType::Validated, [
            'valid' => true,
            'fingerprinted' => $fingerprint !== null,
        ]);

        return ValidationResult::valid($license);
    }

    private function fail(License $license, string $reason): ValidationResult
    {
        $this->events->log($license, LicenseEventType::Validated, ['valid' => false, 'reason' => $reason]);

        return ValidationResult::invalid($reason, $license);
    }
}
