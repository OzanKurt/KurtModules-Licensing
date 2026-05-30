<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
