<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Schemas;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\CustomerQuote;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Webkul\ProgressStepper\Enums\Size;
use Webkul\ProgressStepper\Infolists\Components\ProgressStepper;

class CustomerQuoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProgressStepper::make('status')
                    ->hiddenLabel()
                    ->optionsFromEnum(QuoteStatus::class)
                    ->size(Size::Large)
                    ->columnSpanFull(),

                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan(8)
                            ->columns(3)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label('Numéro')
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('chantier.name')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat),

                                TextEntry::make('expires_at')
                                    ->label('Expiration du devis')
                                    ->date('d/m/Y')
                                    ->color(fn (CustomerQuote $record) => $record->is_expired ? 'danger' : 'gray')
                                    ->weight(fn (CustomerQuote $record) => $record->is_expired ? 'bold' : 'normal'),
                            ]),

                        Callout::make('Etat du devis')
                            ->columnSpan(4)
                            ->color(fn (CustomerQuote $record) => $record->status->getColor())
                            ->icon(fn (CustomerQuote $record) => $record->status->getIcon())
                            ->description(fn (CustomerQuote $record) => $record->status->getLabel()),
                    ]),

                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Callout::make('Total HT')
                            ->color('primary')
                            ->description(fn (CustomerQuote $record) => Number::currency($record->total_ht, 'EUR', 'fr')),
                        Callout::make('Total TVA')
                            ->color('primary')
                            ->description(fn (CustomerQuote $record) => Number::currency($record->total_tva, 'EUR', 'fr')),

                        Callout::make('Total TTC')
                            ->color('success')
                            ->icon(Phosphor::CurrencyEur)
                            ->iconSize('lg')
                            ->description(fn (CustomerQuote $record) => Number::currency($record->total_ttc, 'EUR', 'fr')),
                    ]),
            ]);
    }
}
