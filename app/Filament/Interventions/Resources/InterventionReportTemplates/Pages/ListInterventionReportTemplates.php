<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates\Pages;

use App\Filament\Interventions\Resources\InterventionReportTemplates\InterventionReportTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterventionReportTemplates extends ListRecords
{
    protected static string $resource = InterventionReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
