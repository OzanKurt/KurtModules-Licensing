<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Data;

use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Outcome of validating a key. `$reason` is a stable machine code (e.g.
 * "expired", "revoked", "not_found", "fingerprint_unknown") so clients can
 * branch and localise the message themselves.
 */
final readonly class ValidationResult
{
    public function __construct(
        public bool $valid,
        public ?string $reason = null,
        public ?License $license = null,
    ) {}

    public static function valid(License $license): self
    {
        return new self(true, null, $license);
    }

    public static function invalid(string $reason, ?License $license = null): self
    {
        return new self(false, $reason, $license);
    }
}
