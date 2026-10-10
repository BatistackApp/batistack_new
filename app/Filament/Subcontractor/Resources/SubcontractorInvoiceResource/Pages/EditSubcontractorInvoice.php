<?php

namespace App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource;
use App\Models\Commerce\SubcontractorSituation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubcontractorInvoice extends EditRecord
{
    protected static string $resource = SubcontractorInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (SubcontractorSituation $record) => $record->status === InvoiceStatus::DRAFT),
        ];
    }
}
