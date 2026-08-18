<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates\Pages;

use App\Filament\Interventions\Resources\InterventionReportTemplates\InterventionReportTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInterventionReportTemplate extends CreateRecord
{
    protected static string $resource = InterventionReportTemplateResource::class;
}