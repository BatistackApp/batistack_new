<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts;

use App\Filament\Banque\Resources\Banque\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Banque\Resources\Banque\BankAccounts\Pages\EditBankAccount;
use App\Filament\Banque\Resources\Banque\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Banque\Resources\Banque\BankAccounts\Schemas\BankAccountForm;
use App\Filament\Banque\Resources\Banque\BankAccounts\Tables\BankAccountsTable;
use App\Models\Banque\BankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'Compte Bancaire';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Comptes Bancaires';
    }

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table);
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
            'index' => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'edit' => EditBankAccount::route('/{record}/edit'),
        ];
    }
}
