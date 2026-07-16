<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\RelationManagers;

use App\Models\Commerce\PurchaseOrderItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Lignes de réception';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purchase_order_item_id')
                    ->label('Ligne de commande')
                    ->options(function (RelationManager $livewire) {
                        return PurchaseOrderItem::where('purchase_order_id', $livewire->getOwnerRecord()->purchase_order_id)
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),
                TextInput::make('quantity_received')
                    ->label('Quantité reçue')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('items.name')->label('Désignation (Commande)'),
                TextColumn::make('quantity_received')->label('Quantité reçue'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Tables\Actions\EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
