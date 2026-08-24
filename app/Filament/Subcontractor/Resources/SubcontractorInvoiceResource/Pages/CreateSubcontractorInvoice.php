<?php

namespace App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubcontractorInvoice extends CreateRecord
{
    protected static string $resource = SubcontractorInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->contact && $user->contact->thirdParty) {
            $data['subcontractor_id'] = $user->contact->thirdParty->id;
        }
        $data['status'] = InvoiceStatus::DRAFT;

        return $data;
    }
}
