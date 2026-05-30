<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;
use Kurt\Modules\Licensing\Server\Data\IssuedLicense;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * Mints a new license for a product: generates the plaintext key, stores only
 * its hash + prefix, applies the product's default policy (overridable per
 * call), and records the issuance event. The plaintext key is returned once,
 * inside IssuedLicense.
 */
final class LicenseIssuer
{
    public function __construct(
        private readonly KeyGenerator $keys,
        private readonly KeyHasher $hasher,
        private readonly EventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  At minimum `licensee_email`. May override
     *                                            policy_type, max_activations, expires_at, etc.
     */
    public function issue(Product $product, array $attributes): IssuedLicense
    {
        $key = $this->keys->generate();
        $policy = $product->default_policy ?? [];

        $license = $product->licenses()->create(array_merge(
            [
                'status' => LicenseStatus::Active->value,
                'policy_type' => $policy['type'] ?? PolicyType::Perpetual->value,
                'max_activations' => $policy['max_activations'] ?? 1,
                'issued_at' => now(),
            ],
            $attributes,
            [
                'key_hash' => $this->hasher->hash($key),
                'key_prefix' => $this->keys->prefix($key),
            ],
        ));

        $this->events->log($license, LicenseEventType::Issued, [
            'email' => $license->licensee_email,
            'policy' => $license->policy_type->value,
        ]);

        return new IssuedLicense($key, $license);
    }
}
