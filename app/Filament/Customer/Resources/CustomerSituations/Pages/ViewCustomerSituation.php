<?php

namespace App\Filament\Customer\Resources\CustomerSituations\Pages;

use App\Filament\Customer\Resources\CustomerSituations\CustomerSituationResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerSituation extends ViewRecord
{
    protected static string $resource = CustomerSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan(8)
                            ->columns(4)
                            ->schema([
                                TextEntry::make('number')
                                    ->label('Numéro')
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('order.reference')
                                    ->label('Commande')
                                    ->icon(Phosphor::FileText)
                                    ->placeholder('—'),

                                TextEntry::make('chantier.name')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat)
                                    ->placeholder('—'),

                                TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge(),

                                TextEntry::make('periode_start')
                                    ->label('Période du')
                                    ->date('d/m/Y')
                                    ->icon(Phosphor::Calendar),

                                TextEntry::make('periode_end')
                                    ->label('au')
                                    ->date('d/m/Y'),

                                TextEntry::make('total_ht')
                                    ->label('Montant HT')
                                    ->money('EUR'),

                                TextEntry::make('total_ttc')
                                    ->label('Montant TTC')
                                    ->money('EUR'),
                            ]),

                        Section::make('Détails')
                            ->columnSpan(4)
                            ->schema([
                                TextEntry::make('total_ht')
                                    ->label('Total HT')
                                    ->money('EUR'),

                                TextEntry::make('retenue_garantie_amount')
                                    ->label('Retenue garantie')
                                    ->money('EUR')
                                    ->placeholder('—'),

                                TextEntry::make('prorata_amount')
                                    ->label('Prorata')
                                    ->money('EUR')
                                    ->placeholder('—'),

                                TextEntry::make('total_ttc')
                                    ->label('Total TTC')
                                    ->money('EUR'),
                            ]),
                    ]),
            ]);
    }
}
