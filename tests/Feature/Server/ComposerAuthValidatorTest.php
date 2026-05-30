<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ComposerAuthValidator;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;

it('authorizes a covered package for an active license', function () {
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('OK-1')]);

    $decision = app(ComposerAuthValidator::class)->authorize('OK-1', 'acme/premium');

    expect($decision->allowed)->toBeTrue();
});

it('denies a package the license does not cover', function () {
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('NO-1')]);

    $decision = app(ComposerAuthValidator::class)->authorize('NO-1', 'acme/other');

    expect($decision->allowed)->toBeFalse();
    expect($decision->reason)->toBe('package_not_covered');
});

it('denies an unknown key', function () {
    expect(app(ComposerAuthValidator::class)->authorize('GHOST', 'acme/premium')->reason)
        ->toBe('not_found');
});

it('gates downloads by release date for updates-window policies', function () {
    $cutoff = Carbon::now();
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    License::factory()->for($product)->updatesWindow($cutoff)
        ->create(['key_hash' => app(KeyHasher::class)->hash('WIN-1')]);

    $validator = app(ComposerAuthValidator::class);

    expect($validator->authorize('WIN-1', 'acme/premium', $cutoff->copy()->subDay())->allowed)->toBeTrue();
    expect($validator->authorize('WIN-1', 'acme/premium', $cutoff->copy()->addDay())->allowed)->toBeFalse();
});

it('audits every decision', function () {
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    $license = License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('AUD-1')]);

    app(ComposerAuthValidator::class)->authorize('AUD-1', 'acme/premium');
    app(ComposerAuthValidator::class)->authorize('AUD-1', 'acme/other');

    expect($license->events()->where('action', 'composer_authorized')->count())->toBe(1);
    expect($license->events()->where('action', 'composer_denied')->count())->toBe(1);
});
