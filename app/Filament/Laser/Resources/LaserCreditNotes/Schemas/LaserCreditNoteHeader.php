<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Components\MetadataEntry;

class LaserCreditNoteHeader
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Header::make()
                ->heading(fn (Model $record): string => 'Avoir '.$record->reference)
                ->description(fn (Model $record): ?string => $record->client?->name)
                ->initials(fn (Model $record): string => $record->client?->name ?? $record->reference)
                ->badges([
                    TextEntry::make('status')->badge()->label('Statut'),
                ])
                ->metadata([
                    MetadataEntry::make('reference')->label('Référence'),
                    MetadataEntry::make('client.name')->label('Client'),
                    MetadataEntry::make('invoice.reference')->label('Facture d’origine'),
                    MetadataEntry::make('reason')->label('Motif'),
                ])
                ->summary([
                    TextEntry::make('total_ht')
                        ->label('Total HT')
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                    TextEntry::make('total_tva')
                        ->label('TVA')
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                    TextEntry::make('total_ttc')
                        ->label('Total TTC')
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                ]),
        ]);
    }
}
