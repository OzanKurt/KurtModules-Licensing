<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Licensing\Filament\V4\Resources\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
