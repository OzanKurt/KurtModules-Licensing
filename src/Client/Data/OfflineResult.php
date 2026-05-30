<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client\Data;

/**
 * Result of verifying a downloadable signed license file with no server call.
 * `$reason` is a stable code: "malformed", "bad_signature", "expired".
 */
final readonly class OfflineResult
{
    /**
     * @param  array<array-key, mixed>  $claims
     */
    public function __construct(
        public bool $valid,
        public ?string $reason = null,
        public array $claims = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $claims
     */
    public static function valid(array $claims): self
    {
        return new self(true, null, $claims);
    }

    public static function invalid(string $reason): self
    {
        return new self(false, $reason);
    }
}
