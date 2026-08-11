<?php

namespace App\Filament\Flottes\Resources\Vehicles\RelationManagers;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class InventoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventories';

    protected static ?string $title = 'Inventaire de l\'Outillage Embarqué';

    protected static string|BackedEnum|null $icon = Phosphor::Stack;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Matériel de valeur rattaché au fourgon')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('item_id')
                            ->label('Équipement / Gros outillage')
                            ->options(Item::whereIn('type', [ItemType::STOCKABLE, ItemType::CONSUMABLE])->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('serial_number')
                            ->label('Numéro de série / Tag Unique')
                            ->required()
                            ->placeholder('ex: HLT-70-12345'),
                        TextInput::make('quantity')->label('Quantité')
                            ->label('Quantité')
                            ->numeric()
                            ->required()
                            ->default(1),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                TextColumn::make('item.name')
                    ->label('Désignation de l\'outillage')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('item.reference')
                    ->label('Réf. ERP')
                    ->fontFamily('mono')
                    ->color('gray'),
                TextColumn::make('serial_number')
                    ->label('Numéro de série unique')
                    ->fontFamily('mono')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('quantity')->label('Quantité')
                    ->label('Quantité')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Assigner du matériel'),
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

    public function isReadOnly(): bool
    {
        return false;
    }
}
