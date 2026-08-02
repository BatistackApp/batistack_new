<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Pages;

use App\Filament\Salarie\Resources\ExpenseAdvances\ExpenseAdvanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseAdvances extends ListRecords
{
    protected static string $resource = ExpenseAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
