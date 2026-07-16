<?php

namespace App\Filament\RH\Resources\ExpenseReports\Pages;

use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseReports extends ListRecords
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
