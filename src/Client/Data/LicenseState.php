<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client\Data;

/**
 * The verdict LicenseManager::check() returns to the host application.
 *
 * - valid:   confirmed by the server right now
 * - grace:   server unreachable, but a recent success is cached and still
 *            inside the offline grace window — keep running
 * - invalid: the server says no, or grace has lapsed — gate the feature
 */
final readonly class LicenseState
{
    public const VALID = 'valid';

    public const GRACE = 'grace';

    public const INVALID = 'invalid';

    /**
     * @param  array<array-key, mixed>  $claims
     */
    public function __construct(
        public string $status,
        public ?string $reason = null,
        public array $claims = [],
    ) {}

    public function ok(): bool
    {
        return $this->status === self::VALID || $this->status === self::GRACE;
    }

    public function isGrace(): bool
    {
        return $this->status === self::GRACE;
    }

    /**
     * @param  array<array-key, mixed>  $claims
     */
    public static function valid(array $claims): self
    {
        return new self(self::VALID, null, $claims);
    }

    /**
     * @param  array<array-key, mixed>  $claims
     */
    public static function grace(string $reason, array $claims = []): self
    {
        return new self(self::GRACE, $reason, $claims);
    }

    public static function invalid(string $reason): self
    {
        return new self(self::INVALID, $reason);
    }
}
