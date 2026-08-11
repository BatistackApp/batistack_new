<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssetTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('fixedAsset.name')
                    ->label('Fixed asset'),
                TextEntry::make('fromChantier.name')
                    ->label('From chantier')
                    ->placeholder('-'),
                TextEntry::make('toChantier.name')
                    ->label('To chantier'),
                TextEntry::make('requested_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('transfer_date')
                    ->date(),
                TextEntry::make('status')->label('Statut'),
                TextEntry::make('notes')->label('Notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
