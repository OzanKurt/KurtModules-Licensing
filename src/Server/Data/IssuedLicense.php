<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Data;

use Kurt\Modules\Licensing\Server\Models\License;

/**
 * The result of issuing a license. `$key` is the plaintext license key — it is
 * shown to the buyer exactly once and never persisted in recoverable form, so
 * callers must surface it immediately (email, download, API response).
 */
final readonly class IssuedLicense
{
    public function __construct(
        public string $key,
        public License $license,
    ) {}
}
