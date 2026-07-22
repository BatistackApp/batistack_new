<?php

namespace App\Filament\Gpao\ManufacturingOrders\Pages;

use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditManufacturingOrder extends EditRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
