<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Pages;

use App\Filament\Salarie\Resources\ExpenseAdvances\ExpenseAdvanceResource;
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
