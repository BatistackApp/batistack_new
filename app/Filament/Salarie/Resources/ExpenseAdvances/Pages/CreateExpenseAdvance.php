<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Pages;

use App\Filament\Salarie\Resources\ExpenseAdvances\ExpenseAdvanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseAdvance extends CreateRecord
{
    protected static string $resource = ExpenseAdvanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employeeId = \App\Models\RH\Employee::where('user_id', auth()->id())->value('id');
        $data['employee_id'] = $employeeId;
        $data['status'] = \App\Enums\RH\ExpenseAdvanceStatus::PENDING;

        return $data;
    }
}
