<?php

namespace App\Filament\Articles\Resources\Items\RelationManagers;

use App\Enums\Articles\ItemType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Nomenclature de l\'ouvrage';

    protected static string|null|\BackedEnum $icon = Phosphor::Stack;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === ItemType::WORK;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('child_item_id')
                    ->label('Composant (Article ou MO)')
                    ->relationship('childItem', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('quantity')
                    ->label('Quantité théorique')
                    ->numeric()
                    ->default(1)
                    ->required(),
                TextInput::make('loss_percentage')
                    ->label('% Perte')
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->helperText('La perte est incluse dans le prix de vente.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('childItem.name')->label('Désignation'),
                TextColumn::make('childItem.type')->badge(),
                TextColumn::make('quantity')->label('Qté'),
                TextColumn::make('loss_percentage')->label('Perte (%)')->suffix('%'),
                TextColumn::make('subtotal_cost')
                    ->label('Sous-total Coût')
                    ->getStateUsing(fn ($record) => $record->quantity * (1 + $record->loss_percentage / 100) * $record->childItem->purchase_price)
                    ->money('EUR'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un composant'),
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
