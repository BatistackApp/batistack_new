<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerOrders extends ListRecords
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
