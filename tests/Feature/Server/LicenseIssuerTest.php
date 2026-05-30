<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Enums\PolicyType;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;

it('issues a license, returns the plaintext key, and stores only its hash', function () {
    $product = Product::factory()->create([
        'default_policy' => ['type' => 'subscription', 'max_activations' => 3],
    ]);

    $issued = app(LicenseIssuer::class)->issue($product, [
        'licensee_email' => 'buyer@example.com',
        'expires_at' => now()->addYear(),
    ]);

    expect($issued->key)->toContain('-');
    expect($issued->license->licensee_email)->toBe('buyer@example.com');
    expect($issued->license->policy_type)->toBe(PolicyType::Subscription);
    expect($issued->license->max_activations)->toBe(3);
    expect($issued->license->key_prefix)->toBe(explode('-', $issued->key)[0]);
    expect(app(KeyHasher::class)->matches($issued->key, $issued->license->key_hash))->toBeTrue();
});

it('records an issuance audit event', function () {
    $product = Product::factory()->create();

    $issued = app(LicenseIssuer::class)->issue($product, ['licensee_email' => 'a@b.com']);

    expect($issued->license->events()->where('action', 'issued')->count())->toBe(1);
});

it('lets explicit attributes override the product default policy', function () {
    $product = Product::factory()->create([
        'default_policy' => ['type' => 'perpetual', 'max_activations' => 1],
    ]);

    $issued = app(LicenseIssuer::class)->issue($product, [
        'licensee_email' => 'a@b.com',
        'max_activations' => 5,
    ]);

    expect($issued->license->max_activations)->toBe(5);
});
