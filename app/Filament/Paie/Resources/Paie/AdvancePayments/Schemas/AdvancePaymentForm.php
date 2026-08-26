<?php

namespace App\Filament\Paie\Resources\Paie\AdvancePayments\Schemas;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\AdvancePaymentType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdvancePaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'last_name')
                    ->required()
                    ->searchable(),
                TextInput::make('amount')->label('Montant')
                    ->label('Montant')
                    ->required()
                    ->numeric(),
                DatePicker::make('request_date')
                    ->label('Date de demande')
                    ->required(),
                DatePicker::make('payment_date')
                    ->label('Date de paiement (Optionnel)'),
                Select::make('type')->label('Type')
                    ->options(AdvancePaymentType::class)
                    ->required()
                    ->default(AdvancePaymentType::CLASSIC),
                Select::make('status')->label('Statut')
                    ->options(AdvancePaymentStatus::class)
                    ->required()
                    ->default(AdvancePaymentStatus::PENDING),
                Textarea::make('notes')->label('Notes')
                    ->columnSpanFull(),
            ]);
    }
}
