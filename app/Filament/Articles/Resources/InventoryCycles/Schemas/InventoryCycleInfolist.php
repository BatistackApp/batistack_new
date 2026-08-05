<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryCycleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('warehouse.name')
                    ->label('Dépôt'),
                TextEntry::make('name')
                    ->label('Nom du cycle'),
                TextEntry::make('status')
                    ->label('Statut')
                    ->badge(),
                TextEntry::make('creator.name')
                    ->label('Créé par')
                    ->placeholder('-'),
                TextEntry::make('approver.name')
                    ->label('Approuvé par')
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->label('Date d\'approbation')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
