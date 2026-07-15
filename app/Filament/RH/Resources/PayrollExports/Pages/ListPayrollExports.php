<?php

namespace App\Filament\RH\Resources\PayrollExports\Pages;

use App\Filament\RH\Resources\PayrollExports\PayrollExportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollExports extends ListRecords
{
    protected static string $resource = PayrollExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
