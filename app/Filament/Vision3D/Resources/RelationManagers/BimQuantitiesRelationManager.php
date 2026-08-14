<?php

namespace App\Filament\Vision3D\Resources\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BimQuantitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'quantities';

    protected static ?string $recordTitleAttribute = 'element_name';

    protected static ?string $title = 'Quantitatifs (BOM)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_id')
                    ->label('Article')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('element_name')
                    ->label('Élément (maquette)')
                    ->maxLength(255),
                TextInput::make('unit')
                    ->label('Unité')
                    ->maxLength(50),
                TextInput::make('quantity_required')
                    ->label('Quantité requise')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.name')->label('Article')
                    ->searchable(),
                Tables\Columns\TextColumn::make('element_name')->label('Élément (maquette)')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('unit')->label('Unité')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity_required')
                    ->label('Quantité requise')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
