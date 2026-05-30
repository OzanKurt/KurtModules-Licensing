<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource;
use Kurt\Modules\Licensing\Filament\V5\Resources\ProductResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

/**
 * @return array<string, array{0: class-string, 1: string, 2: array<int, string>}>
 */
dataset('licensing-resources', [
    'Product' => [ProductResource::class, 'ListProducts', ['name', 'slug', 'is_active']],
    'License' => [LicenseResource::class, 'ListLicenses', ['key_prefix', 'status', 'policy_type']],
]);

it('registers an index page and exposes its key table columns', function (string $resource, string $listClass, array $columns) {
    $pageClass = $resource.'\\Pages\\'.$listClass;

    expect(array_keys($resource::getPages()))->toContain('index');
    expect(tableColumnNames($resource, $pageClass))->toContain(...$columns);
})->with('licensing-resources');

it('builds a form with at least one field', function (string $resource, string $listClass, array $columns) {
    $pageClass = $resource.'\\Pages\\'.$listClass;

    expect(formFieldNames($resource, $pageClass))->not->toBeEmpty();
})->with('licensing-resources');

it('filters the license table by status and policy', function () {
    expect(tableFilterNames(LicenseResource::class, LicenseResource::class.'\\Pages\\ListLicenses'))
        ->toContain('status', 'policy_type');
});

it('exposes the product policy + composer-package fields', function () {
    expect(formFieldNames(ProductResource::class, ProductResource::class.'\\Pages\\ListProducts'))
        ->toContain('composer_packages', 'default_policy.type', 'default_policy.max_activations');
});
