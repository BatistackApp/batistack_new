<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Components\MetadataEntry;

class LaserInvoiceHeader
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Header::make()
                ->heading(fn (Model $record): string => 'Facture '.$record->reference)
                ->description(fn (Model $record): ?string => $record->client?->name)
                ->initials(fn (Model $record): string => $record->client?->name ?? $record->reference)
                ->badges([
                    TextEntry::make('status')->badge()->label('Statut'),
                ])
                ->metadata([
                    MetadataEntry::make('reference')->label('Référence'),
                    MetadataEntry::make('client.name')->label('Client'),
                    MetadataEntry::make('order.reference')->label('Commande'),
                    MetadataEntry::make('due_date')->label('Échéance')->date('d/m/Y'),
                ])
                ->summary([
                    TextEntry::make('total_ht')
                        ->label('Total HT')
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->columns(2)
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                    TextEntry::make('total_tva')
                        ->label('TVA')
                        ->columns(2)
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                    TextEntry::make('total_ttc')
                        ->label('Total TTC')
                        ->columns(2)
                        ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' €')
                        ->extraAttributes(['class' => 'whitespace-nowrap']),
                ]),
        ]);
    }
}
