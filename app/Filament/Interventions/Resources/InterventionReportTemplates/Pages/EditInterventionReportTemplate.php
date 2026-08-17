<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates\Pages;

use App\Filament\Interventions\Resources\InterventionReportTemplates\InterventionReportTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInterventionReportTemplate extends EditRecord
{
    protected static string $resource = InterventionReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}