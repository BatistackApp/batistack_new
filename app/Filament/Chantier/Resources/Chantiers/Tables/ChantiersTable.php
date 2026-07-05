<?php

namespace App\Filament\Chantier\Resources\Chantiers\Tables;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Chantiers\ChantierStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Chantiers\ChantierDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Chantier')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->client->name),
                TextColumn::make('status')
                    ->label('État')
                    ->badge(),
                TextColumn::make('progress')
                    ->label('Avancement')
                    ->getStateUsing(fn (Chantier $record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'].' %')
                    ->badge()
                    ->color(fn ($state) => (float) $state >= 100 ? 'success' : 'primary'),
                TextColumn::make('budget_hours')
                    ->label('Heures')
                    ->description(fn (Chantier $record) => 'Réel : '.$record->real_hours.'h')
                    ->color(fn (Chantier $record) => $record->real_hours > $record->budget_hours ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChantierStatus::class),
                SelectFilter::make('manager_id')->label('Conducteur')->relationship('manager', 'last_name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print_os')
                        ->label('Ordre de Service')
                        ->icon(Phosphor::FilePdf)
                        ->color('info')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => response()->download($service->generateStartOrder($record))),
                    Action::make('print_bilan')
                        ->label('Bilan Analytique')
                        ->icon(Phosphor::ChartPie)
                        ->color('success')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => response()->download($service->generateRentabilityReport($record))),

                    Action::make('affect_material')
                        ->label('Affecter Matériel')
                        ->icon(Phosphor::Package)
                        ->color('warning')
                        ->schema([
                            Select::make('warehouse_id')
                                ->label('Dépôt Source')
                                ->options(Warehouse::pluck('name', 'id'))
                                ->required()
                                ->reactive(),
                            Select::make('item_id')
                                ->label('Article')
                                ->options(function (callable $get) {
                                    $warehouseId = $get('warehouse_id');
                                    if (! $warehouseId) {
                                        return [];
                                    }

                                    return Item::whereHas('stocks', function ($q) use ($warehouseId) {
                                        $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0);
                                    })->pluck('name', 'id');
                                })
                                ->required(),
                            TextInput::make('quantity')
                                ->label('Quantité')
                                ->numeric()
                                ->required()
                                ->minValue(0.01),
                        ])
                        ->action(function (Chantier $record, array $data) {
                            $stock = Stock::where('warehouse_id', $data['warehouse_id'])
                                ->where('item_id', $data['item_id'])
                                ->firstOrFail();

                            $stock->decrease(
                                $data['quantity'],
                                "Affectation au chantier {$record->reference}",
                                StockMouvementSource::SITE,
                                $record->id
                            );

                            Notification::make()
                                ->title('Matériel affecté')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
