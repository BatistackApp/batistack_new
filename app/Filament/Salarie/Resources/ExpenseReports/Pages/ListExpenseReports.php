<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Pages;

use App\Filament\Salarie\Resources\ExpenseReports\ExpenseReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseReports extends ListRecords
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $employeeId = \App\Models\RH\Employee::where('user_id', auth()->id())->value('id');
                    $data['employee_id'] = $employeeId;
                    return $data;
                }),
        ];
    }
}
