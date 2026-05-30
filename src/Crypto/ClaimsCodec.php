<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Crypto;

use RuntimeException;

/**
 * Deterministic canonical encoding of license claims. The server signs the
 * output of encode(); the client verifies against encode() of the same claims.
 * Both sides MUST agree byte-for-byte, so keys are sorted recursively and the
 * JSON flags are fixed. Framework-free — the client embeds this verbatim.
 */
final class ClaimsCodec
{
    /**
     * @param  array<array-key, mixed>  $claims
     */
    public static function encode(array $claims): string
    {
        $json = json_encode(
            self::sortRecursive($claims),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException('Unable to encode license claims to canonical JSON.');
        }

        return $json;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function sortRecursive(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortRecursive($item);
            }
        }

        return $value;
    }
}
