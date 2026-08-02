<?php

namespace App\Filament\RH\Resources\ExpenseAdvances\Pages;

use App\Filament\RH\Resources\ExpenseAdvances\ExpenseAdvanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseAdvance extends EditRecord
{
    protected static string $resource = ExpenseAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
