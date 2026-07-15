<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Pages;

use App\Filament\Salarie\Resources\ExpenseReports\ExpenseReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseReport extends EditRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
