<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

/**
 * Hashes plaintext keys for storage and lookup. A keyed HMAC-SHA256 (not
 * bcrypt) is used deliberately: lookups must be deterministic so an incoming
 * key can be matched against `licenses.key_hash` in a single indexed query,
 * while the server-side secret still prevents offline brute-forcing of a
 * leaked database. Keys are normalised first so formatting (dashes, case)
 * never affects the hash.
 */
final class KeyHasher
{
    public function __construct(private readonly string $secret) {}

    public function hash(string $key): string
    {
        return hash_hmac('sha256', $this->normalize($key), $this->secret);
    }

    public function matches(string $key, string $hash): bool
    {
        return hash_equals($hash, $this->hash($key));
    }

    private function normalize(string $key): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $key) ?? '');
    }
}
