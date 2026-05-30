<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Tests;

use Kurt\Modules\Core\Providers\CoreServiceProvider;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\Licensing\Providers\LicensingServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            LicensingServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
