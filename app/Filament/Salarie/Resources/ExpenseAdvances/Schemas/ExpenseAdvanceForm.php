<?php

namespace App\Filament\Salarie\Resources\ExpenseAdvances\Schemas;

use Filament\Schemas\Schema;

class ExpenseAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Détails de la demande')
                    ->columnSpanFull()
                    ->description('Veuillez renseigner le montant, la date prévue et le motif de votre avance sur frais.')
                    ->icon('heroicon-o-currency-euro')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('amount')->label('Montant')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->prefix('€')
                            ->label('Montant demandé')
                            ->columnSpan(1),

                        \Filament\Forms\Components\DatePicker::make('request_date')
                            ->required()
                            ->default(now())
                            ->label('Date de la demande')
                            ->columnSpan(1),

                        \Filament\Forms\Components\Textarea::make('reason')
                            ->required()
                            ->columnSpanFull()
                            ->rows(3)
                            ->label('Motif du déplacement / Dépense')
                            ->placeholder('Ex: Déplacement client Paris, Réservation Hôtel, etc.'),
                    ])
                    ->columns(2),
            ]);
    }
}
