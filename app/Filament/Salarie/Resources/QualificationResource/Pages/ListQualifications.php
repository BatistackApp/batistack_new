<?php

namespace App\Filament\Salarie\Resources\QualificationResource\Pages;

use App\Filament\Salarie\Resources\QualificationResource;
use Filament\Resources\Pages\ListRecords;

class ListQualifications extends ListRecords
{
    protected static string $resource = QualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
