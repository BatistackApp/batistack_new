<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Schemas;

use App\Enums\Paie\PayslipStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PayslipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
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
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(PayslipStatus::class)
                    ->required()
                    ->default(PayslipStatus::DRAFT),

                Repeater::make('custom_bonuses')
                    ->label('Primes et éléments variables exceptionnels')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('label')
                            ->label('Libellé de la prime')
                            ->required(),
                        TextInput::make('amount')->label('Montant')
                            ->label('Montant (€)')
                            ->numeric()
                            ->required(),
                        Toggle::make('is_taxable')
                            ->label('Soumis à cotisations (Brut)')
                            ->inline(false)
                            ->default(true),
                    ]),
            ]);
    }
}
