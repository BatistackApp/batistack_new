<?php

namespace App\Filament\Banque\Resources\Banque\BankTransactions\Schemas;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name')
                    ->required(),
                TextInput::make('external_id')
                    ->label('ID Externe')
                    ->disabled()
                    ->dehydrated(false),
                DatePicker::make('date')->label('Date')
                    ->label('Date')
                    ->required(),
                TextInput::make('description')->label('Description')
                    ->label('Libellé')
                    ->required(),
                TextInput::make('amount')->label('Montant')
                    ->label('Montant')
                    ->required()
                    ->numeric(),
                Select::make('type')->label('Type')
                    ->label('Type')
                    ->options(TransactionType::class)
                    ->required(),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(TransactionStatus::class)
                    ->default('pending')
                    ->required(),
            ]);
    }
}
