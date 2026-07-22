<?php

namespace App\Filament\Gpao\ManufacturingOrders\Pages;

use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturingOrder extends CreateRecord
{
    protected static string $resource = ManufacturingOrderResource::class;
}
