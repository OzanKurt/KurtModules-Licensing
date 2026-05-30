<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Illuminate\Contracts\Cache\Repository;
use Kurt\Modules\Licensing\Client\Contracts\LicenseCache;

/**
 * Adapts any Laravel cache store to the LicenseCache contract so the offline
 * grace window survives across requests. Uses illuminate/contracts only.
 */
final class IlluminateLicenseCache implements LicenseCache
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix = 'licensing:',
    ) {}

    public function get(string $key): ?array
    {
        $value = $this->cache->get($this->prefix.$key);

        return is_array($value) ? $value : null;
    }

    public function put(string $key, array $payload, int $ttlSeconds): void
    {
        $this->cache->put($this->prefix.$key, $payload, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($this->prefix.$key);
    }
}
