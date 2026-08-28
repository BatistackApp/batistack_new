<?php

namespace App\Filament\Subcontractor\Resources\ChantierAssignmentsResource\Pages;

use App\Filament\Subcontractor\Resources\ChantierAssignmentsResource;
use Filament\Resources\Pages\ListRecords;

class ListChantierAssignments extends ListRecords
{
    protected static string $resource = ChantierAssignmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
