<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Pages;

use App\Filament\Commerce\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\ContractingGuardService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function beforeCreate(): void
    {
        $supplier = ThirdParty::find($this->data['supplier_id'] ?? null);

        if ($supplier && app(ContractingGuardService::class)->blocked($supplier)) {
            Notification::make()
                ->title('Commande bloquée')
                ->body(app(ContractingGuardService::class)->reason($supplier))
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
