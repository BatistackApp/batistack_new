<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\RelationManagers;

use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Lignes de facture';

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
                TextInput::make('price_unit')
                    ->label('Prix Unitaire HT')
                    ->numeric()
                    ->required(),
                Select::make('vat_rate_id')
                    ->label('TVA')
                    ->options(VatRate::pluck('name', 'id'))
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
                TextColumn::make('price_unit')->label('PU HT')->money('EUR'),
                TextColumn::make('vatRate.rate')->label('TVA (%)'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
