<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V3\Resources\LicenseResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Licensing\Filament\V3\Resources\LicenseResource;

class EditLicense extends EditRecord
{
    protected static string $resource = LicenseResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
