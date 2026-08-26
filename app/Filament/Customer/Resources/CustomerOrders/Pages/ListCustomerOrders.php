<?php

namespace App\Filament\Customer\Resources\CustomerOrders\Pages;

use App\Filament\Customer\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerOrders extends ListRecords
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
