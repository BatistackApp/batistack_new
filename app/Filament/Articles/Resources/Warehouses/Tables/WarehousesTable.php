<?php

namespace App\Filament\Articles\Resources\Warehouses\Tables;

use App\Filament\Articles\Resources\Warehouses\Pages\BinInventoryPage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Dépôt')->sortable()->searchable(),
                TextColumn::make('location')->label('Adresse'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([
                Action::make('binInventory')
                    ->label('Inventaire bins')
                    ->icon(Phosphor::Scan)
                    ->color('info')
                    ->url(fn ($record) => BinInventoryPage::getUrl('bin-inventory', ['record' => $record->id])),
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
