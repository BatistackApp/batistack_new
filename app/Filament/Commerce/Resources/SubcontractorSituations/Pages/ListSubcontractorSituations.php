<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations\Pages;

use App\Filament\Commerce\Resources\SubcontractorSituations\SubcontractorSituationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubcontractorSituations extends ListRecords
{
    protected static string $resource = SubcontractorSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
