<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts\Pages;

use App\Filament\Banque\Resources\Banque\BankAccounts\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
