<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client\Contracts;

use Kurt\Modules\Licensing\Client\Data\ValidationResponse;

/**
 * How the client SDK talks to a license server. The default implementation is
 * HTTP, but consumers can swap in a fake (tests) or an alternative transport
 * without touching LicenseManager.
 */
interface LicenseTransport
{
    public function validate(string $key, ?string $fingerprint = null): ValidationResponse;

    public function activate(string $key, string $fingerprint, ?string $label = null): ValidationResponse;

    public function deactivate(string $key, string $fingerprint): bool;
}
