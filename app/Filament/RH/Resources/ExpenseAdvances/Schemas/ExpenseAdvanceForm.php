<?php

namespace App\Filament\RH\Resources\ExpenseAdvances\Schemas;

use Filament\Schemas\Schema;

class ExpenseAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\RH\Employee $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Employé'),

                \Filament\Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix('€')
                    ->label('Montant demandé'),

                \Filament\Forms\Components\DatePicker::make('request_date')
                    ->required()
                    ->default(now())
                    ->label('Date de la demande'),

                \Filament\Forms\Components\Textarea::make('reason')
                    ->required()
                    ->columnSpanFull()
                    ->label('Motif du déplacement / Dépense'),

                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        \App\Enums\RH\ExpenseAdvanceStatus::PENDING->value => \App\Enums\RH\ExpenseAdvanceStatus::PENDING->getLabel(),
                        \App\Enums\RH\ExpenseAdvanceStatus::APPROVED->value => \App\Enums\RH\ExpenseAdvanceStatus::APPROVED->getLabel(),
                        \App\Enums\RH\ExpenseAdvanceStatus::REJECTED->value => \App\Enums\RH\ExpenseAdvanceStatus::REJECTED->getLabel(),
                    ])
                    ->default(\App\Enums\RH\ExpenseAdvanceStatus::PENDING)
                    ->required()
                    ->label('Statut'),
            ]);
    }
}
