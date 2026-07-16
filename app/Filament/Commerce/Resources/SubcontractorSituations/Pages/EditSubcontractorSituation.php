<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations\Pages;

use App\Filament\Commerce\Resources\SubcontractorSituations\SubcontractorSituationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubcontractorSituation extends EditRecord
{
    protected static string $resource = SubcontractorSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
