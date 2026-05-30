<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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
