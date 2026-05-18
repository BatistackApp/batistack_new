<?php

namespace App\Filament\Flottes\Resources\TrafficFines\Pages;

use App\Filament\Flottes\Resources\TrafficFines\TrafficFineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrafficFine extends EditRecord
{
    protected static string $resource = TrafficFineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
