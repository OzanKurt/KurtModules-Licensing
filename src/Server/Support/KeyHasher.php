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

    /**
     * Keyed hash of an arbitrary value that must be stored verbatim (no license
     * key normalisation). The `$domain` prefix keeps these digests in their own
     * namespace so, for example, a device fingerprint hash can never collide
     * with a license-key hash even under the same secret.
     */
    public function hmac(string $value, string $domain = ''): string
    {
        return hash_hmac('sha256', $domain.$value, $this->secret);
    }

    /**
     * Constant-time comparison helper. Kept for completeness / external callers:
     * the server itself never uses it, since key lookups resolve through an
     * indexed `where('key_hash', $this->hash($key))` query rather than fetching a
     * row and comparing after the fact.
     *
     * @internal
     */
    public function matches(string $key, string $hash): bool
    {
        return hash_equals($hash, $this->hash($key));
    }

    private function normalize(string $key): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $key) ?? '');
    }
}
