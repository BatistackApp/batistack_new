<?php

namespace App\Filament\RH\Resources\ExpenseReports\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('month')
                    ->label('Mois')
                    ->required()
                    ->numeric(),
                TextInput::make('year')
                    ->label('Année')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'submitted' => 'Soumise',
                        'validated' => 'Validée',
                        'paid' => 'Payée',
                    ])
                    ->required()
                    ->default('draft'),
                TextInput::make('total_amount')
                    ->label('Montant Total')
                    ->required()
                    ->numeric()
                    ->suffix('€')
                    ->default(0.0),
            ]);
    }
}
