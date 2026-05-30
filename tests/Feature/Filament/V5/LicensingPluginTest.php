<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Licensing\Filament\LicensingPlugin;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource;
use Kurt\Modules\Licensing\Filament\V5\Resources\ProductResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('dispatches the facade to the v5 plugin', function () {
    expect(LicensingPlugin::make())->toBeInstanceOf(Kurt\Modules\Licensing\Filament\V5\LicensingPlugin::class)
        ->and(LicensingPlugin::make()->getId())->toBe('kurtmodules-licensing');
});

it('registers the product + license resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(ProductResource::class)
        ->toContain(LicenseResource::class);
});

it('registers a list route for each resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/products')
        ->toContain('admin/licenses');
});
