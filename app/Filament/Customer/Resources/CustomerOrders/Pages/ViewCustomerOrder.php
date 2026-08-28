<?php

namespace App\Filament\Customer\Resources\CustomerOrders\Pages;

use App\Filament\Commerce\Resources\CustomerOrders\Actions\PrinterAction;
use App\Filament\Customer\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerOrder extends ViewRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrinterAction::make(),
        ];
    }
}
