<?php

namespace App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;

use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubcontractorInvoices extends ListRecords
{
    protected static string $resource = SubcontractorInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
