<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

/**
 * Derives a stable, opaque machine fingerprint from host signals. The same
 * machine produces the same hash across runs (so reinstalls don't burn seats),
 * while an optional salt lets a host scope the fingerprint per install or per
 * tenant. No raw host data leaves the machine — only the SHA-256 digest.
 */
final class Fingerprint
{
    public static function generate(?string $salt = null): string
    {
        $hostname = gethostname();

        $signals = [
            php_uname('s'),
            php_uname('n'),
            php_uname('m'),
            $hostname === false ? '' : $hostname,
            PHP_VERSION,
            $salt ?? '',
        ];

        return hash('sha256', implode('|', $signals));
    }
}
