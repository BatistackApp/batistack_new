<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests\Pages;

use App\Filament\Commerce\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
