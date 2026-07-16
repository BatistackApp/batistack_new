<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\RelationManagers;

use App\Models\Commerce\CustomerOrderItem;
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
    protected static ?string $title = 'Lignes de situation';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_order_item_id')
                    ->label('Ligne de commande')
                    ->options(function (RelationManager $livewire) {
                        return CustomerOrderItem::where('customer_order_id', $livewire->getOwnerRecord()->customer_order_id)
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),
                TextInput::make('progress_percentage')
                    ->label('Avancement (%)')
                    ->numeric()
                    ->required()
                    ->maxValue(100)
                    ->minValue(0),
                TextInput::make('amount_ht')
                    ->label('Montant HT')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('item.name')->label('Désignation'),
                TextColumn::make('progress_percentage')->label('Avancement (%)'),
                TextColumn::make('amount_ht')->label('Montant HT')->money('EUR'),
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
