<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Schemas;

use Filament\Schemas\Schema;

class ExpenseAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('amount')
                    ->numeric()
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
            ]);
    }
}
