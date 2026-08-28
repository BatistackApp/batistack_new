<?php

namespace App\Filament\Subcontractor\Resources\AssignedTaskResource\Pages;

use App\Filament\Subcontractor\Resources\AssignedTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListAssignedTasks extends ListRecords
{
    protected static string $resource = AssignedTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
