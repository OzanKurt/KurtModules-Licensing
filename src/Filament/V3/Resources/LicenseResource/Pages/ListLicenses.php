<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V3\Resources\LicenseResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Licensing\Filament\V3\Resources\LicenseResource;

class ListLicenses extends ListRecords
{
    protected static string $resource = LicenseResource::class;
}
