<?php

namespace App\Filament\Chantier\Resources\ChecklistTemplates\Pages;

use App\Filament\Chantier\Resources\ChecklistTemplates\ChecklistTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplate extends EditRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
