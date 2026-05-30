<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Kurt\Modules\Licensing\Client\Contracts\LicenseCache;
use Kurt\Modules\Licensing\Client\Contracts\LicenseTransport;
use Kurt\Modules\Licensing\Client\Data\LicenseState;
use Kurt\Modules\Licensing\Client\Data\ValidationResponse;
use Throwable;

/**
 * The façade a premium package uses to gate itself. check() validates online
 * and, when the server is unreachable, falls back to the last cached success
 * for a configurable grace window — so a network blip never bricks a paying
 * customer, but a genuinely invalid or long-offline license eventually gates.
 */
final class LicenseManager
{
    public function __construct(
        private readonly string $key,
        private readonly LicenseTransport $transport,
        private readonly LicenseCache $cache,
        private readonly int $graceDays = 14,
        private readonly ?string $fingerprint = null,
    ) {}

    public function check(): LicenseState
    {
        try {
            $response = $this->transport->validate($this->key, $this->fingerprint());

            if ($response->valid) {
                $this->remember($response);

                return LicenseState::valid($response->claims);
            }

            // The server is reachable and authoritative: a "no" is final.
            $this->cache->forget($this->cacheKey());

            return LicenseState::invalid($response->reason ?? 'invalid');
        } catch (Throwable $e) {
            return $this->graceFromCache($e->getMessage());
        }
    }

    public function activate(?string $label = null): ValidationResponse
    {
        $response = $this->transport->activate($this->key, $this->fingerprint(), $label);

        if ($response->valid) {
            $this->remember($response);
        }

        return $response;
    }

    public function deactivate(): bool
    {
        $deactivated = $this->transport->deactivate($this->key, $this->fingerprint());

        if ($deactivated) {
            $this->cache->forget($this->cacheKey());
        }

        return $deactivated;
    }

    private function graceFromCache(string $reason): LicenseState
    {
        $cached = $this->cache->get($this->cacheKey());

        if ($cached === null) {
            return LicenseState::invalid('unreachable');
        }

        if (($cached['valid'] ?? false) !== true) {
            return LicenseState::invalid('invalid');
        }

        $claims = is_array($cached['claims'] ?? null) ? $cached['claims'] : [];

        return LicenseState::grace('offline: '.$reason, $claims);
    }

    private function remember(ValidationResponse $response): void
    {
        $this->cache->put($this->cacheKey(), $response->toArray(), $this->graceDays * 86400);
    }

    private function fingerprint(): string
    {
        return $this->fingerprint ?? Fingerprint::generate();
    }

    private function cacheKey(): string
    {
        return 'state:'.substr(hash('sha256', $this->key), 0, 32);
    }
}
