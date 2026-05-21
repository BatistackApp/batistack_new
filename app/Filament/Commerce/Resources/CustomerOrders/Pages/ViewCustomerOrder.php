<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerOrder extends ViewRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
