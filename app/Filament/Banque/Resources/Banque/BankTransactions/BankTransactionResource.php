<?php

namespace App\Filament\Banque\Resources\Banque\BankTransactions;

use App\Filament\Banque\Resources\Banque\BankTransactions\Pages\CreateBankTransaction;
use App\Filament\Banque\Resources\Banque\BankTransactions\Pages\EditBankTransaction;
use App\Filament\Banque\Resources\Banque\BankTransactions\Pages\ListBankTransactions;
use App\Filament\Banque\Resources\Banque\BankTransactions\Schemas\BankTransactionForm;
use App\Filament\Banque\Resources\Banque\BankTransactions\Tables\BankTransactionsTable;
use App\Models\Banque\BankTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankTransactionResource extends Resource
{
    protected static ?string $model = BankTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?string $recordTitleAttribute = 'description';

    public static function getModelLabel(): string
    {
        return 'Transaction Bancaire';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lignes Bancaires';
    }

    public static function form(Schema $schema): Schema
    {
        return BankTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankTransactions::route('/'),
            'create' => CreateBankTransaction::route('/create'),
            'edit' => EditBankTransaction::route('/{record}/edit'),
        ];
    }
}
