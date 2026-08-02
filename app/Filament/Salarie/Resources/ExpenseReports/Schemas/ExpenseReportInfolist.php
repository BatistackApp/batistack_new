<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Schemas;

use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails de la note de frais')
                    ->schema([
                        TextEntry::make('month')
                            ->label('Mois')
                            ->formatStateUsing(fn ($state) => Carbon::create()->day(1)->month($state)->translatedFormat('F')),
                        TextEntry::make('year')
                            ->label('Année'),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('total_amount')
                            ->label('Montant Total')
                            ->money('EUR'),
                    ])->columns(4),
            ]);
    }
}
