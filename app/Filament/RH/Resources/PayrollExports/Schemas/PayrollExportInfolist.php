<?php

namespace App\Filament\RH\Resources\PayrollExports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PayrollExportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('month')
                    ->numeric(),
                TextEntry::make('year')
                    ->numeric(),
                TextEntry::make('status')->label('Statut')
                    ->badge(),
                TextEntry::make('total_employees')
                    ->numeric(),
                TextEntry::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
