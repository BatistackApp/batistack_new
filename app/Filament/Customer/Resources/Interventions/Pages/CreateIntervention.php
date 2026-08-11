<?php

namespace App\Filament\Customer\Resources\Interventions\Pages;

use App\Filament\Customer\Resources\Interventions\InterventionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntervention extends CreateRecord
{
    protected static string $resource = InterventionResource::class;
}
