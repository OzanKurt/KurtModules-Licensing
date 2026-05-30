<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Server\Models\License;

it('expires active subscriptions whose expiry has passed and audits each', function () {
    $license = License::factory()->expired()->create();
    expect($license->status)->toBe(LicenseStatus::Active);

    $this->artisan('licensing:expire')->assertSuccessful();

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::Expired);
    expect($license->events()->where('action', 'expired')->count())->toBe(1);
});

it('leaves perpetual and not-yet-expired licenses active', function () {
    $perpetual = License::factory()->create();
    $future = License::factory()->subscription(now()->addYear())->create();

    $this->artisan('licensing:expire')->assertSuccessful();

    $perpetual->refresh();
    $future->refresh();
    expect($perpetual->status)->toBe(LicenseStatus::Active);
    expect($future->status)->toBe(LicenseStatus::Active);
});
