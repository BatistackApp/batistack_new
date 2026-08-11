<?php

namespace App\Filament\Articles\Resources\Items\Tables;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->circular(),

                TextColumn::make('reference')->label('Référence')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('barcode')
                    ->label('Code-barres')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono'),

                TextColumn::make('name')->label('Nom')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('purchase_price')
                    ->label('Coût / PUMP')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('PV HT')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('stocks_sum_quantity')
                    ->label('Stock Global')
                    ->sum('stocks', 'quantity')
                    ->suffix(fn (Item $record) => " {$record->unit->symbol}")
                    ->color(fn ($state, Item $record) => $state <= 0 ? 'danger' : 'success')
                    ->visible(fn ($record) => $record?->type === ItemType::STOCKABLE),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')
                    ->options(ItemType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('printLabels')
                        ->label('Imprimer Étiquettes')
                        ->icon('heroicon-o-printer')
                        ->form([
                            \Filament\Forms\Components\Select::make('format')
                                ->label("Format d'impression")
                                ->options([
                                    'a4' => 'Planche A4 (Avery 3x7)',
                                    'dymo_28_89' => 'Thermique Dymo (28x89mm)',
                                    'dymo_59_190' => 'Thermique Dymo (59x190mm)',
                                ])
                                ->required()
                                ->default('a4'),
                            \Filament\Forms\Components\TextInput::make('copies')
                                ->label('Nombre de copies par article')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->minValue(1)
                                ->maxValue(100),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $service = app(\App\Services\Articles\ArticleDocumentService::class);
                            $path = $service->generateLabels($records, $data['format'], (int)$data['copies']);
                            return $service->download($path);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
