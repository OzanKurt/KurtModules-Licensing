<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;

function licenseWithKey(string $key, string $state = 'active'): License
{
    $factory = License::factory();
    $factory = match ($state) {
        'revoked' => $factory->revoked(),
        'expired' => $factory->expired(),
        'suspended' => $factory->suspended(),
        default => $factory,
    };

    return $factory->create(['key_hash' => app(KeyHasher::class)->hash($key)]);
}

it('validates an active license by its key', function () {
    $license = licenseWithKey('ABCD-1234');

    $result = app(LicenseValidator::class)->validateKey('ABCD-1234');

    expect($result->valid)->toBeTrue();
    expect($result->license?->is($license))->toBeTrue();
});

it('reports not_found for an unknown key', function () {
    $result = app(LicenseValidator::class)->validateKey('ZZZZ-ZZZZ');

    expect($result->valid)->toBeFalse();
    expect($result->reason)->toBe('not_found');
});

it('reports the precise reason for unusable licenses', function () {
    licenseWithKey('REV-OKED', 'revoked');
    licenseWithKey('EXP-IRED', 'expired');
    licenseWithKey('SUS-PEND', 'suspended');

    $validator = app(LicenseValidator::class);

    expect($validator->validateKey('REV-OKED')->reason)->toBe('revoked');
    expect($validator->validateKey('EXP-IRED')->reason)->toBe('expired');
    expect($validator->validateKey('SUS-PEND')->reason)->toBe('suspended');
});

it('binds validation to a known activation fingerprint', function () {
    $license = licenseWithKey('FP-BOUND');

    expect(app(LicenseValidator::class)->validateKey('FP-BOUND', 'machine-x')->reason)
        ->toBe('fingerprint_unknown');

    app(ActivationManager::class)->activate($license, 'machine-x');

    expect(app(LicenseValidator::class)->validateKey('FP-BOUND', 'machine-x')->valid)
        ->toBeTrue();
});
