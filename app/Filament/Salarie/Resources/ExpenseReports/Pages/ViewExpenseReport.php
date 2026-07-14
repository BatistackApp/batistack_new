<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Pages;

use App\Filament\Salarie\Resources\ExpenseReports\ExpenseReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseReport extends ViewRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
