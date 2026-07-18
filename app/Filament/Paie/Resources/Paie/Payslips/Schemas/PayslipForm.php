<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayslipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('employee_id')
                    ->label('Salarié')
                    ->relationship('employee', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('period')
                    ->label('Période (YYYY-MM)')
                    ->required()
                    ->default(now()->format('Y-m')),
                TextInput::make('base_hours')
                    ->label('Heures de base')
                    ->required()
                    ->numeric()
                    ->default(151.67),
                TextInput::make('hourly_rate')
                    ->label('Taux horaire')
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Paie\PayslipStatus::class)
                    ->required()
                    ->default(\App\Enums\Paie\PayslipStatus::DRAFT),
                    
                \Filament\Forms\Components\Repeater::make('custom_bonuses')
                    ->label('Primes et éléments variables exceptionnels')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('label')
                            ->label('Libellé de la prime')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Montant (€)')
                            ->numeric()
                            ->required(),
                        \Filament\Forms\Components\Toggle::make('is_taxable')
                            ->label('Soumis à cotisations (Brut)')
                            ->inline(false)
                            ->default(true),
                    ]),
            ]);
    }
}
