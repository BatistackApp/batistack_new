<?php

namespace App\Filament\Chantier\Resources\Chantiers\ChantierResource\RelationManagers;

use App\Models\Articles\Item;
use App\Models\Articles\Warehouse;
use App\Services\Articles\StockLogisticsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Schemas\Components as SchemaComponents;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Stocks sur chantier';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-cube';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Article')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.reference')
                    ->label('Référence')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité sur site')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('approvisionner')
                    ->label('Approvisionner')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('source_warehouse_id')
                            ->label('Depuis le Dépôt')
                            ->options(Warehouse::whereNull('chantier_id')->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('item_id')
                            ->label('Article')
                            ->options(Item::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                    ])
                    ->action(function (array $data, array $arguments, $livewire): void {
                        $chantier = $livewire->getOwnerRecord();
                        $source = Warehouse::find($data['source_warehouse_id']);
                        $item = Item::find($data['item_id']);
                        $service = app(StockLogisticsService::class);
                        
                        try {
                            $service->transferToChantier($source, $chantier, $item, (float) $data['quantity'], auth()->id());
                            Notification::make()
                                ->title('Approvisionnement réussi')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de l\'approvisionnement')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('consommer')
                    ->label('Consommer')
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité à consommer')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\TextInput::make('description')
                            ->label('Description / Motif')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, \App\Models\Articles\Stock $record, $livewire): void {
                        $chantier = $livewire->getOwnerRecord();
                        $service = app(StockLogisticsService::class);
                        
                        try {
                            $service->consumeOnSite($chantier, $record->item, (float) $data['quantity'], auth()->id(), $data['description']);
                            Notification::make()
                                ->title('Consommation enregistrée')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('retourner')
                    ->label('Retour Dépôt')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('destination_warehouse_id')
                            ->label('Vers le Dépôt')
                            ->options(Warehouse::whereNull('chantier_id')->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité à retourner')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn (\App\Models\Articles\Stock $record) => $record->quantity),
                    ])
                    ->action(function (array $data, \App\Models\Articles\Stock $record, $livewire): void {
                        $chantier = $livewire->getOwnerRecord();
                        $destination = Warehouse::find($data['destination_warehouse_id']);
                        $service = app(StockLogisticsService::class);
                        
                        try {
                            $service->returnToDepot($chantier, $destination, $record->item, (float) $data['quantity'], auth()->id());
                            Notification::make()
                                ->title('Retour enregistré')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Erreur lors du retour')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
