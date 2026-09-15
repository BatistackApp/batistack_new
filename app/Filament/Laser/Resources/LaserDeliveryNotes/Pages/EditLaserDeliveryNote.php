<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\Pages;

use App\Filament\Laser\Resources\LaserDeliveryNotes\LaserDeliveryNoteResource;
use Filament\Resources\Pages\EditRecord;

class EditLaserDeliveryNote extends EditRecord
{
    protected static string $resource = LaserDeliveryNoteResource::class;

    protected static ?string $title = 'Modifier le bon de livraison';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
