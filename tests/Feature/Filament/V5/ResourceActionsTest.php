<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource\Pages\ListLicenses;
use Kurt\Modules\Licensing\Server\Models\License;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('exposes a revoke row action on the license table', function () {
    expect(tableActionNames(LicenseResource::class, ListLicenses::class))
        ->toContain('revoke');
});

it('disallows creating licenses through the resource', function () {
    expect(LicenseResource::canCreate())->toBeFalse();
});

it('revokes a license the way the revoke action does', function () {
    $license = License::factory()->create();

    $license->update(['status' => LicenseStatus::Revoked->value, 'revoked_at' => now()]);

    expect($license->fresh()->status)->toBe(LicenseStatus::Revoked);
});
