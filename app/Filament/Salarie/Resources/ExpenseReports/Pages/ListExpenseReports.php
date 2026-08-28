<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Pages;

use App\Filament\Salarie\Resources\ExpenseReports\ExpenseReportResource;
use App\Models\RH\ExpenseAdvance;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListExpenseReports extends ListRecords
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        $advanceIds = [];

        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data) use (&$advanceIds) {
                    $data['employee_id'] = auth()->user()->getEmployeeIdOrFail();

                    $advanceIds = $data['advance_ids'] ?? [];
                    unset($data['advance_ids']);

                    return $data;
                })
                ->after(function (Model $record) use (&$advanceIds) {
                    if (! empty($advanceIds)) {
                        ExpenseAdvance::whereIn('id', $advanceIds)
                            ->update(['expense_report_id' => $record->id]);
                    }
                }),
        ];
    }
}
