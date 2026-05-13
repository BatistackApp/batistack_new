<?php

namespace App\Filament\Articles\Resources\Items\RelationManagers;

use App\Enums\Articles\ItemType;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';
    protected static ?string $title = 'État des stocks par dépôt';
    protected static string|null|\BackedEnum $icon = Phosphor::Warehouse;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === ItemType::STOCKABLE;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('Dépôt')
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->disabledOn('edit')
                    ->native(false),
                TextInput::make('quantity')
                    ->label('Quantité en stock')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->suffix(fn ($livewire) => $livewire->getOwnerRecord()->unit->symbol),
                TextInput::make('min_threshold')
                    ->label('Seuil d\'alerte')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->helperText('Une notification sera envoyée si le stock descend sous ce seuil.'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('warehouse.name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse.name')
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Dépôt')
                    ->weight('bold'),
                TextColumn::make('quantity')
                    ->label('Disponible')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($record) => $record->quantity <= $record->min_threshold ? 'danger' : 'success')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('min_threshold')
                    ->label('Seuil mini')
                    ->numeric(decimalPlaces: 2)
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Initialiser un dépôt')
                    ->icon(Phosphor::Plus),
                AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
