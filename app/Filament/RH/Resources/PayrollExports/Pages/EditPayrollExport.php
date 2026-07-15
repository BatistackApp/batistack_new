<?php

namespace App\Filament\RH\Resources\PayrollExports\Pages;

use App\Filament\RH\Resources\PayrollExports\PayrollExportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollExport extends EditRecord
{
    protected static string $resource = PayrollExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
