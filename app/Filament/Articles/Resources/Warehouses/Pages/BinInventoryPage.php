<?php

namespace App\Filament\Articles\Resources\Warehouses\Pages;

use App\Filament\Articles\Resources\Warehouses\WarehouseResource;
use App\Models\Articles\StockLocation;
use App\Models\Articles\Warehouse;
use App\Services\Articles\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class BinInventoryPage extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static string $resource = WarehouseResource::class;

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::Scan;

    protected static ?string $navigationLabel = 'Inventaire par emplacement';

    protected static ?string $title = 'Inventaire physique par emplacement';

    protected static string|null|\UnitEnum $navigationGroup = 'Configuration Dépôts';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.articles.resources.warehouses.pages.bin-inventory-page';

    public ?Warehouse $warehouse = null;

    public int $recordId;

    public function mount(int|string $record): void
    {
        $this->recordId = (int) $record;
        $this->warehouse = Warehouse::findOrFail($this->recordId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockLocation::whereHas('stock', fn ($q) => $q->where('warehouse_id', $this->recordId))
                    ->with('stock.item')
                    ->hasQuantity()
                    ->orderBy('location_code')
            )
            ->columns([
                TextColumn::make('location_code')
                    ->label('Emplacement')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('stock.item.reference')
                    ->label('Réf.')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('stock.item.name')
                    ->label('Article')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('quantity')
                    ->label('Quantité théorique')
                    ->numeric(decimalPlaces: 2)
                    ->color('warning'),
            ])
            ->headerActions([
                Action::make('count')
                    ->label('Compter tout')
                    ->icon(Phosphor::CheckSquare)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Inventaire physique')
                    ->modalDescription('Saisissez les quantités physiques comptées pour chaque emplacement. Laissez vide pour les emplacements inchangés.')
                    ->form(fn () => $this->getCountFormFields())
                    ->action(function (array $data): void {
                        $this->processCountData($data);
                    }),
            ])
            ->recordActions([
                Action::make('countBin')
                    ->label('Compter')
                    ->icon(Phosphor::PencilSimple)
                    ->color('primary')
                    ->form([
                        TextInput::make('counted_quantity')
                            ->label('Quantité physique comptée')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (StockLocation $record, array $data): void {
                        $warehouse = Warehouse::findOrFail($this->recordId);

                        app(InventoryService::class)->reconcile(
                            $record->stock->item,
                            $warehouse,
                            $data['counted_quantity'],
                            "Inventaire physique emplacement {$record->location_code}",
                            $record->location_code
                        );

                        Notification::make()
                            ->success()
                            ->title('Emplacement réconcilié')
                            ->body("{$record->location_code}: {$data['counted_quantity']} {$record->stock->item->unit?->symbol}")
                            ->send();
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPagination(50)
            ->emptyStateHeading('Aucun emplacement avec du stock')
            ->emptyStateDescription('Assignez des emplacements aux stocks depuis la fiche article.');
    }

    protected function getCountFormFields(): array
    {
        $locations = StockLocation::whereHas('stock', fn ($q) => $q->where('warehouse_id', $this->recordId))
            ->with('stock.item')
            ->hasQuantity()
            ->orderBy('location_code')
            ->get();

        return $locations->map(function ($loc) {
            return TextInput::make("bins.{$loc->id}")
                ->label("{$loc->location_code} — {$loc->stock->item->reference} ({$loc->stock->item->name})")
                ->helperText("Théorique: {$loc->quantity} {$loc->stock->item->unit?->symbol}")
                ->numeric()
                ->nullable()
                ->minValue(0);
        })->toArray();
    }

    protected function processCountData(array $data): void
    {
        $bins = $data['bins'] ?? [];

        if (empty($bins)) {
            Notification::make()->warning()->title('Aucune modification')->send();

            return;
        }

        $warehouse = Warehouse::findOrFail($this->recordId);
        $count = 0;

        DB::transaction(function () use ($bins, $warehouse, &$count) {
            foreach ($bins as $locationId => $countedQuantity) {
                if ($countedQuantity === null) {
                    continue;
                }

                $location = StockLocation::with('stock.item')->find($locationId);

                if (! $location || ! $location->stock) {
                    continue;
                }

                $theoretical = $location->quantity;

                if ($countedQuantity == $theoretical) {
                    continue;
                }

                app(InventoryService::class)->reconcile(
                    $location->stock->item,
                    $warehouse,
                    $countedQuantity,
                    "Inventaire physique emplacement {$location->location_code}",
                    $location->location_code
                );

                $count++;
            }
        });

        Notification::make()
            ->success()
            ->title('Inventaire validé')
            ->body("{$count} emplacement(s) réconcilié(s).")
            ->send();
    }

    protected function getRecord(): int|string
    {
        return $this->recordId;
    }
}
