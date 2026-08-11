<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts\Schemas;

use App\Enums\Banque\BankAccountType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Société')
                    ->relationship('company', 'legal_name')
                    ->required(),
                TextInput::make('name')->label('Nom')
                    ->label('Nom du compte')
                    ->required(),
                Select::make('type')->label('Type')
                    ->label('Type de compte')
                    ->options(BankAccountType::class)
                    ->default('checking')
                    ->required(),
                TextInput::make('iban')
                    ->label('IBAN'),
                TextInput::make('bic')
                    ->label('BIC'),
                TextInput::make('currency')
                    ->label('Devise')
                    ->required()
                    ->default('EUR'),
                TextInput::make('balance')
                    ->label('Solde initial')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('bankin_item_id')
                    ->label('Identifiant Bankin'),
            ]);
    }
}
