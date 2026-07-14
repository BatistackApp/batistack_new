<?php

namespace App\Filament\RH\Resources\ExpenseReports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExpenseReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.last_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}"),
                TextEntry::make('month')
                    ->label('Mois')
                    ->numeric(),
                TextEntry::make('year')
                    ->label('Année')
                    ->numeric(),
                TextEntry::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getLabel()),
                TextEntry::make('total_amount')
                    ->label('Montant Total')
                    ->numeric()
                    ->suffix(' €'),
                TextEntry::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
