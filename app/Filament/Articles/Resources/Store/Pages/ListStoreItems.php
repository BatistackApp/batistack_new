<?php

namespace App\Filament\Articles\Resources\Store\Pages;

use App\Enums\Articles\StoreCategory;
use App\Filament\Articles\Resources\Items\ItemResource;
use App\Filament\Articles\Resources\Store\Schemas\StoreItemForm;
use App\Filament\Articles\Resources\Store\Schemas\StoreRestockForm;
use App\Filament\Articles\Resources\Store\StoreResource;
use App\Models\Articles\Item;
use App\Services\Articles\StoreService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextInputFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ListStoreItems extends ListRecords
{
    protected static string $resource = StoreResource::class;

    public function getTable(): Table
    {
        $warehouse = app(StoreService::class)->getWarehouse();

        return Table::make()
            ->query(
                Item::storeItems()
                    ->active()
                    ->with(['stocks' => fn ($q) => $q->where('warehouse_id', $warehouse->id)])
            )
            ->columns([
                ImageColumn::make('getFirstMediaUrl')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->grow(false),
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('store_category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn ($state) => StoreCategory::tryFrom($state)?->getLabel() ?? $state),
                TextColumn::make('stock')
                    ->label('Stock Magasin')
                    ->getStateUsing(fn (Item $record) => number_format($record->getStockForStore($warehouse), 0, ',', ' '))
                    ->badge()
                    ->color(fn (Item $record) => $record->getStockForStore($warehouse) <= $record->store_reorder_qty ? 'danger' : 'success'),
                TextColumn::make('store_reorder_qty')
                    ->label('Seuil réappro.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Prix PUMP')
                    ->money('EUR')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('store_category')
                    ->label('Catégorie')
                    ->options(StoreCategory::class),
                TextInputFilter::make('search')
                    ->label('Recherche')
                    ->placeholder('Nom, référence...')
                    ->query(fn ($query, $value) => $query->search($value)),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('withdraw')
                        ->label('Déstockage rapide')
                        ->icon(Phosphor::MinusCircle)
                        ->color('warning')
                        ->form(fn (Item $record) => StoreItemForm::configure($record))
                        ->action(fn (Item $record, array $data) => $this->withdraw($record, $data)),
                    Action::make('restock')
                        ->label('Réapprovisionner')
                        ->icon(Phosphor::PlusCircle)
                        ->color('success')
                        ->form(fn (Item $record) => StoreRestockForm::configure($record))
                        ->action(fn (Item $record, array $data) => $this->restock($record, $data)),
                    Action::make('history')
                        ->label('Historique')
                        ->icon(Phosphor::ClockCounterClockwise)
                        ->color('info')
                        ->url(fn (Item $record) => StoreResource::getUrl('history', ['item' => $record->id])),
                    Action::make('view')
                        ->label('Fiche article')
                        ->icon(Phosphor::Eye)
                        ->url(fn (Item $record) => ItemResource::getUrl('view', ['record' => $record])),
                ]),
            ])
            ->bulkActions([]);
    }

    protected function withdraw(Item $item, array $data): void
    {
        try {
            app(StoreService::class)->quickWithdrawal(
                $item,
                $data['quantity'],
                $data['note'] ?? null,
            );

            Notification::make()
                ->title('Déstockage effectué')
                ->body("{$data['quantity']} unité(s) de {$item->name} retirée(s) du magasin.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de déstockage')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function restock(Item $item, array $data): void
    {
        try {
            app(StoreService::class)->restock(
                $item,
                $data['quantity'],
                $data['purchase_price'],
                $data['batch_number'] ?? null,
            );

            Notification::make()
                ->title('Réapprovisionnement effectué')
                ->body("{$data['quantity']} unité(s) de {$item->name} ajoutée(s) au magasin.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de réapprovisionnement')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
