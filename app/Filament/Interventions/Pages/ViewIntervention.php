<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\InterventionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIntervention extends ViewRecord
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\EditAction::make()],
            InterventionResource::getSharedActions()
        );
    }
}
