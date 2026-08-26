<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Pages;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Filament\Salarie\Resources\ExpenseAdvances\ExpenseAdvanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseAdvance extends CreateRecord
{
    protected static string $resource = ExpenseAdvanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_id'] = auth()->user()->getEmployeeIdOrFail();
        $data['status'] = ExpenseAdvanceStatus::PENDING;

        return $data;
    }
}
