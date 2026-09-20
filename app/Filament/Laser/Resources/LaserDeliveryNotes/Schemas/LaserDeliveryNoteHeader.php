<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Components\MetadataEntry;

class LaserDeliveryNoteHeader
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Header::make()
                ->heading(fn (Model $record): string => 'Bon de livraison '.$record->reference)
                ->description(fn (Model $record): ?string => $record->client?->name)
                ->initials(fn (Model $record): string => $record->client?->name ?? $record->reference)
                ->badges([
                    TextEntry::make('status')->badge()->label('Statut'),
                ])
                ->metadata([
                    MetadataEntry::make('reference')->label('Référence'),
                    MetadataEntry::make('client.name')->label('Client'),
                    MetadataEntry::make('order.reference')->label('Commande d’origine'),
                    MetadataEntry::make('delivery_date')->label('Date de livraison')->date('d/m/Y'),
                ]),
        ]);
    }
}
