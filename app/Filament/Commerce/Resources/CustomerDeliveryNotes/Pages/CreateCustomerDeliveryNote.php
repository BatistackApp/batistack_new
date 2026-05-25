<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Services\Commerce\DeliveryNoteService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomerDeliveryNote extends CreateRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;
    protected static ?string $title = 'Nouveau Bon de Livraison';
    protected static ?string $breadcrumb = 'Nouveau';

    protected function handleRecordCreation(array $data): Model
    {
        $reference = app(DeliveryNoteService::class)->generateReference();

        $data['reference'] = $reference;
        $data['responsable_id'] = auth()->user()->id;

        return CustomerDeliveryNote::create($data);
    }
}
