<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Pages;

use App\Filament\Chantier\Resources\ChantierLogs\ChantierLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChantierLogs extends ListRecords
{
    protected static string $resource = ChantierLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
