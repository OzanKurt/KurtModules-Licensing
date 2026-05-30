<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Enums\LicenseStatus;

it('exposes the four lifecycle states', function () {
    expect(LicenseStatus::Active->value)->toBe('active');
    expect(LicenseStatus::Suspended->value)->toBe('suspended');
    expect(LicenseStatus::Expired->value)->toBe('expired');
    expect(LicenseStatus::Revoked->value)->toBe('revoked');
});

it('treats only active as usable', function () {
    expect(LicenseStatus::Active->isUsable())->toBeTrue();
    expect(LicenseStatus::Suspended->isUsable())->toBeFalse();
    expect(LicenseStatus::Expired->isUsable())->toBeFalse();
    expect(LicenseStatus::Revoked->isUsable())->toBeFalse();
});
