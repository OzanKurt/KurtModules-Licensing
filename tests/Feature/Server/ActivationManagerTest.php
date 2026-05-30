<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Exceptions\ActivationLimitReachedException;
use Kurt\Modules\Licensing\Server\Exceptions\LicenseNotUsableException;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;

it('activates a new machine and consumes one seat', function () {
    $license = License::factory()->seats(2)->create();

    $activation = app(ActivationManager::class)->activate($license, 'machine-1');

    expect($activation->isActive())->toBeTrue();
    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('is idempotent for a repeated fingerprint and does not consume a second seat', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'same-machine');
    $manager->activate($license, 'same-machine');

    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('throws once seats are exhausted', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'machine-1');
    $manager->activate($license, 'machine-2');
})->throws(ActivationLimitReachedException::class);

it('frees a seat on deactivation so a new machine can activate', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'machine-1');
    expect($manager->deactivate($license, 'machine-1'))->toBeTrue();
    expect($license->fresh()->activeActivationsCount())->toBe(0);

    $manager->activate($license, 'machine-2');
    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('refuses to activate a revoked license', function () {
    $license = License::factory()->revoked()->create();

    app(ActivationManager::class)->activate($license, 'machine-1');
})->throws(LicenseNotUsableException::class);
