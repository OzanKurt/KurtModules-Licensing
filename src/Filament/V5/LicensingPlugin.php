<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V5;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource;
use Kurt\Modules\Licensing\Filament\V5\Resources\ProductResource;

final class LicensingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-licensing';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ProductResource::class,
            LicenseResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
