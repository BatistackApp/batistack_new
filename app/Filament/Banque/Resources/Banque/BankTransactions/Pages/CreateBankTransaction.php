<?php

namespace App\Filament\Banque\Resources\Banque\BankTransactions\Pages;

use App\Filament\Banque\Resources\Banque\BankTransactions\BankTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankTransaction extends CreateRecord
{
    protected static string $resource = BankTransactionResource::class;
}
