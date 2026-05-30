<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Facades;

use DateTimeInterface;
use Illuminate\Support\Facades\Facade;
use Kurt\Modules\Licensing\Server\Data\ComposerDecision;
use Kurt\Modules\Licensing\Server\Data\IssuedLicense;
use Kurt\Modules\Licensing\Server\Data\ValidationResult;
use Kurt\Modules\Licensing\Server\Models\Activation;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * @method static IssuedLicense issue(Product $product, array<string, mixed> $attributes)
 * @method static ValidationResult validate(string $key, ?string $fingerprint = null)
 * @method static Activation activate(License $license, string $fingerprint, array<string, string|null> $meta = [])
 * @method static bool deactivate(License $license, string $fingerprint)
 * @method static ComposerDecision authorizeComposer(string $key, string $package, ?DateTimeInterface $releaseDate = null)
 * @method static array{format: string, public_key: string, claims: array<string, mixed>, signature: string} signFileFor(License $license)
 *
 * @see \Kurt\Modules\Licensing\Support\Licensing
 */
final class Licensing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kurt\Modules\Licensing\Support\Licensing::class;
    }
}
