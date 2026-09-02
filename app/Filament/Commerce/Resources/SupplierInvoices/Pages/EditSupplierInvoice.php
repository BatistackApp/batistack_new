<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Pages;

use App\Filament\Commerce\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierInvoice extends EditRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === \App\Enums\Commerce\InvoiceStatus::DRAFT),
        ];
    }
}
