<?php

namespace App\Filament\RH\Resources\PayrollExports\Pages;

use App\Filament\RH\Resources\PayrollExports\PayrollExportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayrollExport extends ViewRecord
{
    protected static string $resource = PayrollExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
