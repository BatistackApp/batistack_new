<?php

namespace App\Filament\Customer\Resources\Interventions\Pages;

use App\Filament\Customer\Resources\Interventions\InterventionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIntervention extends ViewRecord
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
