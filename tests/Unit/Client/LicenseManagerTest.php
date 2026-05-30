<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Client\ArrayLicenseCache;
use Kurt\Modules\Licensing\Client\Data\LicenseState;
use Kurt\Modules\Licensing\Client\Data\ValidationResponse;
use Kurt\Modules\Licensing\Client\LicenseManager;
use Kurt\Modules\Licensing\Tests\Fixtures\FakeLicenseTransport;

function makeManager(FakeLicenseTransport $transport, ArrayLicenseCache $cache): LicenseManager
{
    return new LicenseManager('ABCD-1234', $transport, $cache, graceDays: 14, fingerprint: 'fixed-fp');
}

it('returns valid and caches the result when the server confirms', function () {
    $transport = new FakeLicenseTransport;
    $transport->validateResponse = new ValidationResponse(true, null, ['seats' => 3]);
    $cache = new ArrayLicenseCache;

    $state = makeManager($transport, $cache)->check();

    expect($state->status)->toBe(LicenseState::VALID);
    expect($state->ok())->toBeTrue();
    expect($state->claims)->toBe(['seats' => 3]);
});

it('falls back to a cached grace state when the server is unreachable', function () {
    $transport = new FakeLicenseTransport;
    $transport->validateResponse = new ValidationResponse(true, null, ['seats' => 1]);
    $cache = new ArrayLicenseCache;
    $manager = makeManager($transport, $cache);

    $manager->check();          // primes the cache
    $transport->throw = true;   // server goes down

    $state = $manager->check();

    expect($state->status)->toBe(LicenseState::GRACE);
    expect($state->ok())->toBeTrue();
    expect($state->claims)->toBe(['seats' => 1]);
});

it('gates when the server is unreachable and nothing is cached', function () {
    $transport = new FakeLicenseTransport;
    $transport->throw = true;

    $state = makeManager($transport, new ArrayLicenseCache)->check();

    expect($state->status)->toBe(LicenseState::INVALID);
    expect($state->reason)->toBe('unreachable');
});

it('treats a reachable rejection as final and clears the grace cache', function () {
    $transport = new FakeLicenseTransport;
    $transport->validateResponse = new ValidationResponse(true, null, ['x' => 1]);
    $cache = new ArrayLicenseCache;
    $manager = makeManager($transport, $cache);

    $manager->check(); // cache a valid result
    $transport->validateResponse = new ValidationResponse(false, 'revoked');

    $rejected = $manager->check();
    expect($rejected->status)->toBe(LicenseState::INVALID);
    expect($rejected->reason)->toBe('revoked');

    $transport->throw = true; // no grace, because the cache was cleared
    expect($manager->check()->reason)->toBe('unreachable');
});

it('caches a successful activation and forgets it on deactivation', function () {
    $transport = new FakeLicenseTransport;
    $transport->activateResponse = new ValidationResponse(true, null, ['seats' => 2]);
    $cache = new ArrayLicenseCache;
    $manager = makeManager($transport, $cache);

    expect($manager->activate('My Laptop')->valid)->toBeTrue();

    $transport->throw = true; // activation primed the grace cache
    expect($manager->check()->status)->toBe(LicenseState::GRACE);

    $transport->throw = false;
    expect($manager->deactivate())->toBeTrue();

    $transport->throw = true; // cache cleared on deactivate
    expect($manager->check()->reason)->toBe('unreachable');
});
