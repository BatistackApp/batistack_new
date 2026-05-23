<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use App\Services\Commerce\CustomerOrderService;
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
            Action::make('cancel')
                ->label('Annuler la commande')
                ->icon(Phosphor::Prohibit)
                ->visible(fn (Model $record) => $record->status === OrderStatus::DRAFT || $record->status === OrderStatus::CONFIRMED)
                ->requiresConfirmation()
                ->action(function (Model $record) {
                    $record->update([
                        'status' => OrderStatus::CANCELLED,
                    ]);
                }),
        ];
    }
}
