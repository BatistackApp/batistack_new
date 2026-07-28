<?php

namespace App\Filament\Technicien\Resources\Interventions\Pages;

use App\Filament\Technicien\Resources\Interventions\InterventionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntervention extends EditRecord
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
