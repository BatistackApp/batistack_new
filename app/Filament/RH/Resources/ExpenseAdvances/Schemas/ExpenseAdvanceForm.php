<?php

namespace App\Filament\RH\Resources\ExpenseAdvances\Schemas;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Models\RH\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Employé'),

                TextInput::make('amount')->label('Montant')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix('€')
                    ->label('Montant demandé'),

                DatePicker::make('request_date')
                    ->required()
                    ->default(now())
                    ->label('Date de la demande'),

                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull()
                    ->label('Motif du déplacement / Dépense'),

                Select::make('status')->label('Statut')
                    ->options([
                        ExpenseAdvanceStatus::PENDING->value => ExpenseAdvanceStatus::PENDING->getLabel(),
                        ExpenseAdvanceStatus::APPROVED->value => ExpenseAdvanceStatus::APPROVED->getLabel(),
                        ExpenseAdvanceStatus::REJECTED->value => ExpenseAdvanceStatus::REJECTED->getLabel(),
                    ])
                    ->default(ExpenseAdvanceStatus::PENDING)
                    ->required()
                    ->label('Statut'),
            ]);
    }
}
