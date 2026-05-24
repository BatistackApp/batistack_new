<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerOrder extends EditRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
