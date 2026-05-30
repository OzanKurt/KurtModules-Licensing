<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament;

use Filament\Contracts\Plugin;
use Kurt\Modules\Core\Support\FilamentVersion;
use RuntimeException;

/**
 * Version-dispatching facade for the Licensing Filament plugin.
 *
 * Register on a panel with `->plugin(\Kurt\Modules\Licensing\Filament\LicensingPlugin::make())`.
 * The correct V{n} plugin is resolved from the installed Filament major, so the
 * same call works whether the consumer runs Filament 3, 4, or 5.
 */
final class LicensingPlugin
{
    public static function make(): Plugin
    {
        return match (FilamentVersion::major()) {
            5 => new V5\LicensingPlugin,
            4 => new V4\LicensingPlugin,
            3 => new V3\LicensingPlugin,
            default => throw new RuntimeException('Filament is not installed; cannot register the Licensing plugin.'),
        };
    }
}
