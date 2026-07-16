<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Schemas;

use Filament\Schemas\Schema;

class ExpenseReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\Section::make('Détails de la note de frais')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('month')
                            ->label('Mois')
                            ->formatStateUsing(fn ($state) => \Carbon\Carbon::create()->day(1)->month($state)->translatedFormat('F')),
                        \Filament\Infolists\Components\TextEntry::make('year')
                            ->label('Année'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('total_amount')
                            ->label('Montant Total')
                            ->money('EUR'),
                    ])->columns(4),
            ]);
    }
}
