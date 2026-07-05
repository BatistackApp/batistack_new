<?php

namespace App\Filament\Subcontractor\Resources\ConsultationResource\Pages;

use App\Filament\Subcontractor\Resources\ConsultationResource;
use Filament\Resources\Pages\ListRecords;

class ListConsultations extends ListRecords
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
