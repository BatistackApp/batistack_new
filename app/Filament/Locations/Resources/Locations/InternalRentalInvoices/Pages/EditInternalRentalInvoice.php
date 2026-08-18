<?php

namespace App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Pages;

use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\InternalRentalInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInternalRentalInvoice extends EditRecord
{
    protected static string $resource = InternalRentalInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
