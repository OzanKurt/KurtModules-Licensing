<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class LicensingServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'licensing';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-licensing')
            ->hasConfigFile('licensing')
            ->hasTranslations()
            ->discoversMigrations();
    }
}
