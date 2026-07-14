<?php

namespace App\Filament\Banque\Resources\Banque\BankTransactions\Pages;

use App\Filament\Banque\Resources\Banque\BankTransactions\BankTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankTransactions extends ListRecords
{
    protected static string $resource = BankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
