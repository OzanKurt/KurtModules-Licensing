<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Tests;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Kurt\Modules\Core\Providers\CoreServiceProvider;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\Licensing\Providers\LicensingServiceProvider;
use Kurt\Modules\Licensing\Tests\Fixtures\AdminPanelProvider;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Under Testbench the Livewire singletons lose their shared container
        // binding after the provider's initial registration, which makes every
        // Filament/Livewire component render throw on a null error bag.
        // Re-running register() is idempotent and restores them. No-op when
        // Filament (and therefore Livewire) is not installed.
        if (FilamentVersion::major() !== null && class_exists(LivewireServiceProvider::class)) {
            (new LivewireServiceProvider($this->app))->register();
        }
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_merge([
            CoreServiceProvider::class,
            LicensingServiceProvider::class,
        ], $this->filamentProviders());
    }

    /**
     * Filament + Livewire providers are only registered when Filament is
     * installed, so the non-Filament suites run without them. Each is filtered
     * by class_exists so the list stays valid across the v3/v4/v5 matrix
     * (filament/schemas and filament/actions are v4/v5-only packages).
     *
     * @return array<int, class-string>
     */
    protected function filamentProviders(): array
    {
        if (FilamentVersion::major() === null) {
            return [];
        }

        $candidates = [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            AdminPanelProvider::class,
        ];

        return array_values(array_filter(
            $candidates,
            static fn (string $provider): bool => class_exists($provider),
        ));
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('session.driver', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
