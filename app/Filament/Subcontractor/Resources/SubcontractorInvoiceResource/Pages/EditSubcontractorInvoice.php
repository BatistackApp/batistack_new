<?php

namespace App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;

use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubcontractorInvoice extends EditRecord
{
    protected static string $resource = SubcontractorInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
