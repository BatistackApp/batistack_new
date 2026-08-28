<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerQuoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Information sur le devis')
                            ->columnSpan(8)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('reference')->label('Référence')
                                    ->label('Référence'),

                                TextEntry::make('client.name')
                                    ->label('Client')
                                    ->icon(Phosphor::User),

                                TextEntry::make('chantier.reference')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat)
                                    ->formatStateUsing(fn (Model $record) => $record->chantier?->reference.' - '.$record->chantier?->name),

                                TextEntry::make('status')->label('Statut')
                                    ->columnSpanFull()
                                    ->size(TextSize::Large)
                                    ->label('Etat du devis')
                                    ->icon(fn (Model $record) => $record->status->getIcon())
                                    ->color(fn (Model $record) => $record->status->getColor())
                                    ->formatStateUsing(fn (Model $record) => $record->status->getLabel()),
                            ])
                            ->icon('heroicon-o-bookmark'),

                        Section::make()
                            ->poll('2s')
                            ->columnSpan(4)
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('total_ht')
                                            ->alignEnd()
                                            ->money('eur')
                                            ->label('Total HT'),

                                        TextEntry::make('total_tva')
                                            ->label('Total TVA')
                                            ->alignEnd()
                                            ->money('eur'),

                                        TextEntry::make('total_ttc')
                                            ->alignEnd()
                                            ->money('eur')
                                            ->label('Total TTC'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
