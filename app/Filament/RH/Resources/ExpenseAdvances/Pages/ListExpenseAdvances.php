<?php

namespace App\Filament\RH\Resources\ExpenseAdvances\Pages;

use App\Filament\RH\Resources\ExpenseAdvances\ExpenseAdvanceResource;
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
