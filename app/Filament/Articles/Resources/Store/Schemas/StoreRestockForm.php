<?php

namespace App\Filament\Articles\Resources\Store\Schemas;

use App\Models\Articles\Item;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class StoreRestockForm
{
    public static function configure(?Item $record = null): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('quantity')
                        ->label('Quantité à réapprovisionner')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->suffix('unités'),
                    TextInput::make('purchase_price')
                        ->label('Prix d\'achat HT')
                        ->numeric()
                        ->required()
                        ->prefix('€')
                        ->default(fn () => $record?->purchase_price ?? 0),
                    TextInput::make('batch_number')
                        ->label('Numéro de lot (optionnel)')
                        ->nullable()
                        ->placeholder('Lot fournisseur...'),
                ]),
        ];
    }
}
