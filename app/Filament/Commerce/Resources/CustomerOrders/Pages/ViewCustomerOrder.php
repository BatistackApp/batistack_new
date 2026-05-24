<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\CancelAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\ConfirmedAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\PrinterAction;
use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerOrder extends ViewRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CancelAction::make(),
            ConfirmedAction::make(),
            PrinterAction::make()->iconButton(),
        ];
    }
}
