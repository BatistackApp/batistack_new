<?php

namespace App\Filament\Salarie\Resources\AbsenceResource\Pages;

use App\Filament\Salarie\Resources\AbsenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAbsences extends ManageRecords
{
    protected static string $resource = AbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle Demande'),
        ];
    }
}
