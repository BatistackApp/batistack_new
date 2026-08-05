<?php

namespace App\Filament\Articles\Resources\Items\Schemas;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Services\Articles\ItemService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- HEADER : IDENTITÉ VISUELLE ---
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                SpatieMediaLibraryImageEntry::make('primary_image')
                                    ->label('')
                                    ->collection('primary_image')
                                    ->circular()
                                    ->grow(false),

                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Désignation')
                                            ->weight('bold')
                                            ->size('lg'),
                                        TextEntry::make('reference')
                                            ->label('Référence')
                                            ->fontFamily('mono')
                                            ->copyable(),
                                    ])->columnSpan(2),

                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->badge(),
                                        IconEntry::make('is_active')
                                            ->label('Statut')
                                            ->boolean(),
                                    ])->columnSpan(1),
                            ]),
                    ]),

                // --- TABS : DÉTAILS APPROFONDIS ---
                Tabs::make('Détails')
                    ->tabs([
                        // ONGLET 1 : TECHNIQUE & LOGISTIQUE
                        Tabs\Tab::make('Technique')
                            ->icon(Phosphor::Info)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('unit.name')
                                            ->label('Unité de mesure')
                                            ->icon(Phosphor::Scales),
                                        TextEntry::make('description')
                                            ->label('Notes techniques')
                                            ->placeholder('Aucune description')
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        // ONGLET 2 : FINANCES & VALORISATION
                        Tabs\Tab::make('Finances')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('purchase_price')
                                            ->label(fn (Item $record) => $record->type === ItemType::WORK ? 'Coût de revient (Ouvrage)' : 'Prix d\'achat / PUMP')
                                            ->money('EUR')
                                            ->color('info')
                                            ->weight('bold'),

                                        TextEntry::make('selling_price')
                                            ->label('Prix de vente HT')
                                            ->money('EUR')
                                            ->color('success')
                                            ->weight('bold'),

                                        TextEntry::make('margin')
                                            ->label('Marge brute')
                                            ->getStateUsing(fn (Item $record) => $record->selling_price - $record->purchase_price)
                                            ->money('EUR')
                                            ->color('primary'),

                                        TextEntry::make('vatRate.name')
                                            ->label('Taux de TVA'),
                                    ]),
                            ]),

                        // ONGLET 3 : COMPOSITION (UNIQUEMENT POUR OUVRAGES)
                        Tabs\Tab::make('Nomenclature / Recette')
                            ->icon(Phosphor::Stack)
                            ->visible(fn (Item $record) => $record->type === ItemType::WORK)
                            ->schema([
                                Section::make('Détail du chiffrage')
                                    ->description('Coûts détaillés incluant les coefficients de perte')
                                    ->schema([
                                        TextEntry::make('detailed_costs')
                                            ->label('Analyse analytique')
                                            ->getStateUsing(function (Item $record) {
                                                $costs = app(ItemService::class)->calculateDetailedCost($record);

                                                return "Coût total : {$costs['total_cost']} € (dont perte isolée : {$costs['loss_cost']} €)";
                                            })
                                            ->icon(Phosphor::ChartPieSlice),
                                    ]),
                            ]),

                        // ONGLET 4 : TRACE ET QR CODE
                        Tabs\Tab::make('Traçabilité')
                            ->icon(Phosphor::QrCode)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        SpatieMediaLibraryImageEntry::make('barcode')
                                            ->label('QR Code Identification')
                                            ->collection('barcode')
                                            ->conversion('')
                                            ->square(),

                                        TextEntry::make('updated_at')
                                            ->label('Dernière mise à jour')
                                            ->dateTime('d/m/Y H:i')
                                            ->since(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
