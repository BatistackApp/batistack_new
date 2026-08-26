<?php

namespace App\Filament\RH\Resources\ExpenseReports\Pages;

use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
use App\Models\RH\ExpenseAdvance;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseReport extends CreateRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->advanceIds = $data['advance_ids'] ?? [];
        unset($data['advance_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->advanceIds)) {
            ExpenseAdvance::whereIn('id', $this->advanceIds)
                ->update(['expense_report_id' => $this->record->id]);
        }
    }

    protected array $advanceIds = [];
}
