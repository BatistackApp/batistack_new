<?php

namespace App\Filament\Salarie\Resources\TimeEntryResource\Pages;

use App\Filament\Salarie\Resources\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTimeEntries extends ManageRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Pointer mes heures'),
        ];
    }
}
