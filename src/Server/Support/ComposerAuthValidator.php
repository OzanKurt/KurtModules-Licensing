<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Server\Data\ComposerDecision;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Decides whether a Composer download may proceed. This backs the HTTP-basic
 * bridge that fronts a private Satis/Packagist: the buyer's email is the
 * username and the license key the password. A license only unlocks the
 * Composer packages declared on its product, and `updates_window` policies
 * additionally gate by release date.
 */
final class ComposerAuthValidator
{
    public function __construct(
        private readonly KeyHasher $hasher,
        private readonly EventLogger $events,
    ) {}

    public function authorize(string $key, string $package, ?DateTimeInterface $releaseDate = null): ComposerDecision
    {
        $license = License::query()->where('key_hash', $this->hasher->hash($key))->first();

        if ($license === null) {
            return ComposerDecision::deny('not_found');
        }

        $license->loadMissing('product');
        $packages = $license->product->composer_packages ?? [];

        if ($packages !== [] && ! in_array($package, $packages, true)) {
            return $this->deny($license, $package, 'package_not_covered');
        }

        if (! $license->isUsable()) {
            return $this->deny($license, $package, $this->unusableReason($license));
        }

        if ($releaseDate !== null && ! $license->allowsDownloadOf(Carbon::instance($releaseDate))) {
            return $this->deny($license, $package, 'outside_updates_window');
        }

        $this->events->log($license, LicenseEventType::ComposerAuthorized, ['package' => $package]);

        return ComposerDecision::allow($license);
    }

    private function deny(License $license, string $package, string $reason): ComposerDecision
    {
        $this->events->log($license, LicenseEventType::ComposerDenied, ['package' => $package, 'reason' => $reason]);

        return ComposerDecision::deny($reason, $license);
    }

    private function unusableReason(License $license): string
    {
        return match (true) {
            $license->status === LicenseStatus::Revoked => 'revoked',
            $license->status === LicenseStatus::Suspended => 'suspended',
            $license->hasExpired() => 'expired',
            default => 'inactive',
        };
    }
}
