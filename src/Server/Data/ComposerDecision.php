<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Data;

use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Verdict for a single Composer download attempt. `$reason` is a stable code
 * ("not_found", "package_not_covered", "expired", "revoked", "inactive",
 * "outside_updates_window") used for audit logging and the HTTP 403 body.
 */
final readonly class ComposerDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public ?License $license = null,
    ) {}

    public static function allow(License $license): self
    {
        return new self(true, null, $license);
    }

    public static function deny(string $reason, ?License $license = null): self
    {
        return new self(false, $reason, $license);
    }
}
