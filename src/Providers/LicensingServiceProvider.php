<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Licensing\Client\Contracts\LicenseCache;
use Kurt\Modules\Licensing\Client\Contracts\LicenseTransport;
use Kurt\Modules\Licensing\Client\HttpLicenseTransport;
use Kurt\Modules\Licensing\Client\IlluminateLicenseCache;
use Kurt\Modules\Licensing\Client\LicenseManager;
use Kurt\Modules\Licensing\Client\OfflineVerifier;
use Kurt\Modules\Licensing\Console\Commands\ExpireLicensesCommand;
use Kurt\Modules\Licensing\Console\Commands\GenerateKeysCommand;
use Kurt\Modules\Licensing\Console\Commands\IssueLicenseCommand;
use Kurt\Modules\Licensing\Http\Middleware\AuthenticatesComposer;
use Kurt\Modules\Licensing\Policies\LicensePolicy;
use Kurt\Modules\Licensing\Policies\ProductPolicy;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\ComposerAuthValidator;
use Kurt\Modules\Licensing\Server\Support\EventLogger;
use Kurt\Modules\Licensing\Server\Support\KeyGenerator;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;
use Kurt\Modules\Licensing\Support\Licensing;
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
            ->discoversMigrations()
            ->hasCommands([
                GenerateKeysCommand::class,
                IssueLicenseCommand::class,
                ExpireLicensesCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->registerServerServices();
        $this->registerClientServices();
    }

    public function packageBooted(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('licensing.composer', AuthenticatesComposer::class);

        $this->registerPolicies();

        // Composer download-gating endpoint — separate from the REST API kit and
        // enabled by default so private Composer access keeps working.
        if ((bool) config('licensing.routes.api_enabled', true)) {
            Route::middleware(['throttle:'.(string) config('licensing.routes.throttle', '60,1')])
                ->prefix((string) config('licensing.routes.prefix', 'licensing'))
                ->name('licensing.')
                ->group(__DIR__.'/../../routes/composer.php');
        }

        // Out-of-the-box REST API, built on the Core API kit. No-op while
        // `licensing.http.mode` is headless (the safe default).
        $this->registerModuleApi(__DIR__.'/../../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->app->booted(function (): void {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(ExpireLicensesCommand::class)->daily();
            });
        }
    }

    /**
     * Map the admin API models to their policies. Both policies gate on the
     * host-defined `licensing:manage` ability (deny-by-default), so the admin
     * CRUD endpoints stay locked down until the host opts in.
     */
    private function registerPolicies(): void
    {
        Gate::policy(
            (string) config('licensing.models.license', License::class),
            LicensePolicy::class,
        );
        Gate::policy(
            (string) config('licensing.models.product', Product::class),
            ProductPolicy::class,
        );
    }

    private function registerServerServices(): void
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
            (int) config('licensing.offline.reissue_ttl_days', 7),
        ));

        $this->app->singleton(EventLogger::class);
        $this->app->singleton(LicenseIssuer::class);
        $this->app->singleton(ActivationManager::class);
        $this->app->singleton(LicenseValidator::class);
        $this->app->singleton(ComposerAuthValidator::class);
        $this->app->singleton(Licensing::class);
    }

    /**
     * Client bindings are lazy (bind, not singleton) and read config at resolve
     * time, so a server-only install never constructs an HTTP transport and a
     * client-only install never needs the server services.
     */
    private function registerClientServices(): void
    {
        $this->app->bind(OfflineVerifier::class, fn () => new OfflineVerifier(
            (string) config('licensing.public_key', ''),
            (int) config('licensing.offline.skew_tolerance', 60),
        ));

        $this->app->bind(LicenseCache::class, function () {
            $store = config('licensing.client.cache_store');

            return new IlluminateLicenseCache(
                $this->app->make('cache')->store(is_string($store) ? $store : null),
            );
        });

        $this->app->bind(LicenseTransport::class, fn () => new HttpLicenseTransport(
            $this->app->make(HttpFactory::class),
            rtrim((string) config('licensing.client.server_url', ''), '/'),
        ));

        $this->app->bind(LicenseManager::class, fn () => new LicenseManager(
            (string) config('licensing.client.key', ''),
            $this->app->make(LicenseTransport::class),
            $this->app->make(LicenseCache::class),
            (int) config('licensing.client.grace_days', 14),
        ));
    }
}
