<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Pages;

use App\Filament\Articles\Resources\InventoryCycles\InventoryCycleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryCycle extends ViewRecord
{
    protected static string $resource = InventoryCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
