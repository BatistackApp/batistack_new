<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\InterventionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntervention extends CreateRecord
{
    protected static string $resource = InterventionResource::class;
}
