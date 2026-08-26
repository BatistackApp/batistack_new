<?php

namespace App\Filament\RH\Resources\ExpenseReports\Schemas;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Models\RH\ExpenseAdvance;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
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
                    ->live()
                    ->required(),
                TextInput::make('month')
                    ->label('Mois')
                    ->required()
                    ->numeric(),
                TextInput::make('year')
                    ->label('Année')
                    ->required()
                    ->numeric(),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'submitted' => 'Soumise',
                        'validated' => 'Validée',
                        'paid' => 'Payée',
                    ])
                    ->required()
                    ->default('draft'),
                TextInput::make('total_amount')->label('Montant total')
                    ->label('Montant Total')
                    ->required()
                    ->numeric()
                    ->suffix('€')
                    ->default(0.0),
                Select::make('advance_ids')
                    ->label('Avances à déduire')
                    ->multiple()
                    ->options(fn (Get $get) => ExpenseAdvance::where('employee_id', $get('employee_id'))
                        ->where('status', ExpenseAdvanceStatus::PAID)
                        ->pluck('reason', 'id')
                    )
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->helperText('Sélectionnez les avances déjà versées au salarié qui couvrent ce déplacement.'),
            ]);
    }
}
