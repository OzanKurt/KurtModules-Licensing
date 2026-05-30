<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Kurt\Modules\Licensing\Client\Contracts\LicenseCache;

/**
 * Process-local cache. Fine for a single long-running worker or tests, but it
 * does not survive a request boundary — production should use
 * IlluminateLicenseCache so the offline grace window persists.
 */
final class ArrayLicenseCache implements LicenseCache
{
    /** @var array<string, array{payload: array<array-key, mixed>, expires: int}> */
    private array $store = [];

    public function get(string $key): ?array
    {
        $entry = $this->store[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        if ($entry['expires'] !== 0 && $entry['expires'] < time()) {
            unset($this->store[$key]);

            return null;
        }

        return $entry['payload'];
    }

    public function put(string $key, array $payload, int $ttlSeconds): void
    {
        $this->store[$key] = [
            'payload' => $payload,
            'expires' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
        ];
    }

    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }
}
