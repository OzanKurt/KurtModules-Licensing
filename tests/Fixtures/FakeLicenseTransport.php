<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Tests\Fixtures;

use Kurt\Modules\Licensing\Client\Contracts\LicenseTransport;
use Kurt\Modules\Licensing\Client\Data\ValidationResponse;
use RuntimeException;

/**
 * Programmable in-memory transport for LicenseManager tests. Set `$throw` to
 * simulate an unreachable server; otherwise it returns the configured
 * responses.
 */
final class FakeLicenseTransport implements LicenseTransport
{
    public bool $throw = false;

    public ?ValidationResponse $validateResponse = null;

    public ?ValidationResponse $activateResponse = null;

    public bool $deactivateResult = true;

    public function validate(string $key, ?string $fingerprint = null): ValidationResponse
    {
        $this->guard();

        return $this->validateResponse ?? new ValidationResponse(true);
    }

    public function activate(string $key, string $fingerprint, ?string $label = null): ValidationResponse
    {
        $this->guard();

        return $this->activateResponse ?? new ValidationResponse(true);
    }

    public function deactivate(string $key, string $fingerprint): bool
    {
        $this->guard();

        return $this->deactivateResult;
    }

    private function guard(): void
    {
        if ($this->throw) {
            throw new RuntimeException('network down');
        }
    }
}
