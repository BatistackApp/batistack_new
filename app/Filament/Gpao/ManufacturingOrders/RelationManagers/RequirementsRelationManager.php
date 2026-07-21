<?php

namespace App\Filament\Gpao\ManufacturingOrders\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'requirements';
    protected static ?string $title = 'Matières Requises (Nomenclature)';
    protected static ?string $modelLabel = 'Composant';
    protected static ?string $pluralModelLabel = 'Composants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->label('Article')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Schemas\Components\TextInput::make('quantity_required')
                    ->label('Quantité Requise')
                    ->required()
                    ->numeric(),
                \Filament\Schemas\Components\TextInput::make('quantity_consumed')
                    ->label('Quantité Consommée')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->columns([
                TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable(),
                TextColumn::make('quantity_required')
                    ->label('Qte. Requise')
                    ->numeric(),
                TextColumn::make('quantity_consumed')
                    ->label('Qte. Consommée')
                    ->numeric(),
                TextColumn::make('item.unit.symbol')
                    ->label('Unité'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
