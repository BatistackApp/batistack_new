<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Pages;

use App\Filament\Chantier\Resources\ChantierLogs\ChantierLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChantierLog extends EditRecord
{
    protected static string $resource = ChantierLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
