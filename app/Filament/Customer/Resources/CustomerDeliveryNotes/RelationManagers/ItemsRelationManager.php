<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->paginated(false)
            ->columns([
                IconColumn::make('quantity_in_stock')
                    ->label('')
                    ->icon(fn (Model $record) => $record->quantity_delivered < $record->quantity_ordered ? Phosphor::WarningCircle : Phosphor::CheckCircle)
                    ->color(fn (Model $record) => $record->quantity_delivered < $record->quantity_ordered ? 'warning' : 'success')
                    ->tooltip(fn (Model $record) => $record->quantity_delivered < $record->quantity_ordered ? 'Reste à livrée: '.$record->quantity_undelivered : 'OK'),

                TextColumn::make('item.name')
                    ->label('Désignation'),

                TextColumn::make('quantity_ordered')
                    ->label('Qte Commandée')
                    ->numeric()
                    ->badge(),

                TextColumn::make('quantity_delivered')
                    ->label('Qte Livrée')
                    ->numeric()
                    ->badge(),

            ]);
    }
}
