<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerOrder extends CreateRecord
{
    protected static string $resource = CustomerOrderResource::class;
}
