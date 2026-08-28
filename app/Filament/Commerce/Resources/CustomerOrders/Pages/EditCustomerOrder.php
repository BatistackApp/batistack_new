<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use App\Jobs\Commerce\GenerateManufacturingOrdersJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomerOrder extends EditRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_of')
                ->label('Générer les OF')
                ->icon('phosphor-factory')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    GenerateManufacturingOrdersJob::dispatch($this->record);
                    Notification::make()
                        ->title('Ordres de fabrication générés !')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === OrderStatus::CONFIRMED && $this->record->manufacturingOrders()->count() === 0),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
