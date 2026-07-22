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
            \Filament\Actions\Action::make('generate_of')
                ->label('Générer les OF')
                ->icon('phosphor-factory')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Jobs\Commerce\GenerateManufacturingOrdersJob::dispatch($this->record);
                    \Filament\Notifications\Notification::make()
                        ->title('Ordres de fabrication générés !')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === \App\Enums\Commerce\OrderStatus::CONFIRMED && $this->record->manufacturingOrders()->count() === 0),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
