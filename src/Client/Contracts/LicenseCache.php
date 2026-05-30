<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client\Contracts;

/**
 * Stores the last successful validation so the client can keep working during
 * a configurable offline grace window when the server is unreachable. The
 * default is in-memory (ArrayLicenseCache); production wraps a persistent
 * Laravel cache store via IlluminateLicenseCache.
 */
interface LicenseCache
{
    /**
     * @return array<array-key, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function put(string $key, array $payload, int $ttlSeconds): void;

    public function forget(string $key): void;
}
