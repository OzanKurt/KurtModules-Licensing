<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Support;

use DateTimeInterface;
use Kurt\Modules\Licensing\Server\Data\ComposerDecision;
use Kurt\Modules\Licensing\Server\Data\IssuedLicense;
use Kurt\Modules\Licensing\Server\Data\ValidationResult;
use Kurt\Modules\Licensing\Server\Models\Activation;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\ComposerAuthValidator;
use Kurt\Modules\Licensing\Server\Support\LicenseClaims;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;

/**
 * Single ergonomic entry point over the server services, exposed via the
 * Licensing facade so host apps can `Licensing::issue(...)` /
 * `Licensing::validate(...)` without resolving each service by hand.
 */
final class Licensing
{
    public function __construct(
        private readonly LicenseIssuer $issuer,
        private readonly LicenseValidator $validator,
        private readonly ActivationManager $activations,
        private readonly ComposerAuthValidator $composer,
        private readonly LicenseFileSigner $signer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function issue(Product $product, array $attributes): IssuedLicense
    {
        return $this->issuer->issue($product, $attributes);
    }

    public function validate(string $key, ?string $fingerprint = null): ValidationResult
    {
        return $this->validator->validateKey($key, $fingerprint);
    }

    /**
     * @param  array<string, string|null>  $meta
     */
    public function activate(License $license, string $fingerprint, array $meta = []): Activation
    {
        return $this->activations->activate($license, $fingerprint, $meta);
    }

    public function deactivate(License $license, string $fingerprint): bool
    {
        return $this->activations->deactivate($license, $fingerprint);
    }

    public function authorizeComposer(string $key, string $package, ?DateTimeInterface $releaseDate = null): ComposerDecision
    {
        return $this->composer->authorize($key, $package, $releaseDate);
    }

    /**
     * Build and Ed25519-sign a downloadable license file for a license.
     *
     * @return array{format: string, public_key: string, claims: array<string, mixed>, signature: string}
     */
    public function signFileFor(License $license): array
    {
        return $this->signer->sign(LicenseClaims::build($license));
    }
}
