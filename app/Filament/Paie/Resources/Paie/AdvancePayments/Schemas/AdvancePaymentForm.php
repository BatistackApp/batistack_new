<?php

namespace App\Filament\Paie\Resources\Paie\AdvancePayments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AdvancePaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'last_name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('amount')->label('Montant')
                    ->label('Montant')
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\DatePicker::make('request_date')
                    ->label('Date de demande')
                    ->required(),
                \Filament\Forms\Components\DatePicker::make('payment_date')
                    ->label('Date de paiement (Optionnel)'),
                \Filament\Forms\Components\Select::make('type')->label('Type')
                    ->options(\App\Enums\Paie\AdvancePaymentType::class)
                    ->required()
                    ->default(\App\Enums\Paie\AdvancePaymentType::CLASSIC),
                \Filament\Forms\Components\Select::make('status')->label('Statut')
                    ->options(\App\Enums\Paie\AdvancePaymentStatus::class)
                    ->required()
                    ->default(\App\Enums\Paie\AdvancePaymentStatus::PENDING),
                Textarea::make('notes')->label('Notes')
                    ->columnSpanFull(),
            ]);
    }
}
