<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Builds the public claim set that represents a license — the same shape is
 * returned by the validate API and embedded inside a signed offline file, so
 * online and offline consumers see identical data.
 */
final class LicenseClaims
{
    /**
     * @return array<string, mixed>
     */
    public static function build(License $license): array
    {
        $license->loadMissing('product');

        return [
            'key_prefix' => $license->key_prefix,
            'product' => $license->product->slug,
            'status' => $license->status->value,
            'policy' => $license->policy_type->value,
            'max_activations' => $license->max_activations,
            'issued_at' => $license->issued_at->toIso8601String(),
            'expires_at' => $license->expires_at?->toIso8601String(),
            'updates_until' => $license->updates_until?->toIso8601String(),
            'licensee' => [
                'email' => $license->licensee_email,
                'name' => $license->licensee_name,
            ],
        ];
    }
}
