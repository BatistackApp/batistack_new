<?php

namespace App\Filament\Customer\Resources\Interventions\Pages;

use App\Filament\Customer\Resources\Interventions\InterventionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterventions extends ListRecords
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
