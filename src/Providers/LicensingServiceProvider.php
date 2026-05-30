<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\ComposerAuthValidator;
use Kurt\Modules\Licensing\Server\Support\EventLogger;
use Kurt\Modules\Licensing\Server\Support\KeyGenerator;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(KeyGenerator::class, fn () => new KeyGenerator(
            (int) config('licensing.key.groups', 4),
            (int) config('licensing.key.group_size', 4),
            (string) config('licensing.key.alphabet', 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'),
        ));

        $this->app->singleton(KeyHasher::class, fn () => new KeyHasher(
            (string) (config('licensing.key.hash_secret') ?: config('app.key')),
        ));

        $this->app->singleton(LicenseFileSigner::class, fn () => new LicenseFileSigner(
            (string) config('licensing.signing_key', ''),
            (string) config('licensing.public_key', ''),
        ));

        $this->app->singleton(EventLogger::class);
        $this->app->singleton(LicenseIssuer::class);
        $this->app->singleton(ActivationManager::class);
        $this->app->singleton(LicenseValidator::class);
        $this->app->singleton(ComposerAuthValidator::class);
    }
}
