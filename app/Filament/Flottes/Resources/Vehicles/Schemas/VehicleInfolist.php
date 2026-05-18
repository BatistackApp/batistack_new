<?php

namespace App\Filament\Flottes\Resources\Vehicles\Schemas;

use App\Models\Flottes\Vehicle;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                SpatieMediaLibraryImageEntry::make('avatar')
                                    ->label('')
                                    ->collection('photos')
                                    ->circular()
                                    ->grow(false),
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('license_plate')
                                            ->label('Plaque d\'Immatriculation')
                                            ->weight('bold')
                                            ->size('lg')
                                            ->fontFamily('mono'),
                                        TextEntry::make('reference')
                                            ->label('Code Interne')
                                            ->fontFamily('mono')
                                            ->copyable(),
                                    ])->columnSpan(2),
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->badge(),
                                        TextEntry::make('status')
                                            ->badge(),
                                    ])->columnSpan(1),
                            ]),
                    ]),

                Tabs::make('Bilan Technique & Financier')
                    ->tabs([
                        // --- TECHNIQUE & USAGE ---
                        Tabs\Tab::make('Usage & Carburant')
                            ->icon(Phosphor::Gauge)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('odometer')
                                            ->label('Valeur Compteur')
                                            ->numeric(decimalPlaces: 1)
                                            ->suffix(fn (Vehicle $record) => $record->usage_unit === 'hours' ? ' h' : ' km')
                                            ->weight('bold'),
                                        TextEntry::make('fuel_type')
                                            ->label('Motorisation / Énergie'),
                                        TextEntry::make('crit_air_level')
                                            ->label('Vignette Crit\'Air')
                                            ->badge()
                                            ->placeholder('Aucune'),
                                    ]),
                            ]),

                        // --- INVESTISSEMENT, TCO & AMORTISSEMENT ---
                        Tabs\Tab::make('Coût de Détention (TCO)')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('purchase_price')
                                            ->label('Prix d\'acquisition')
                                            ->money('EUR'),
                                        TextEntry::make('purchase_date')
                                            ->label('Date d\'acquisition')
                                            ->date('d/m/Y'),
                                        TextEntry::make('tco_cache')
                                            ->label('Amortissement Réel (TCO)')
                                            ->money('EUR')
                                            ->color('gray')
                                            ->weight('bold'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
