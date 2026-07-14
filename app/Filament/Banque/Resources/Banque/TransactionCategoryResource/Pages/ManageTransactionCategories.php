<?php

namespace App\Filament\Banque\Resources\Banque\TransactionCategoryResource\Pages;

use App\Filament\Banque\Resources\Banque\TransactionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTransactionCategories extends ManageRecords
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
