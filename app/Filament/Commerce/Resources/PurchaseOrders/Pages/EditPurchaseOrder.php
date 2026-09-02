<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Pages;

use App\Filament\Commerce\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === \App\Enums\Commerce\OrderStatus::DRAFT),
        ];
    }
}
