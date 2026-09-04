<?php

namespace App\Filament\Articles\Resources\Store\Schemas;

use App\Models\Articles\Item;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class StoreItemForm
{
    public static function configure(?Item $record = null): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('quantity')
                        ->label('Quantité à retirer')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn () => $record?->getStockForStore() ?? 9999)
                        ->suffix('unités'),
                    Textarea::make('note')
                        ->label('Note (optionnel)')
                        ->nullable()
                        ->rows(2)
                        ->placeholder('Motif du prélèvement...'),
                ]),
        ];
    }
}
