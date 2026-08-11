<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests\RelationManagers;

use App\Models\Articles\Item;
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
    protected static ?string $title = 'Lignes de demande';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_id')
                    ->label('Article')
                    ->options(Item::pluck('name', 'id'))
                    ->searchable(),
                TextInput::make('name')->label('Nom')
                    ->label('Désignation')
                    ->required(),
                TextInput::make('quantity')->label('Quantité')
                    ->label('Quantité')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Désignation'),
                TextColumn::make('quantity')->label('Quantité'),
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
