<?php

namespace App\Filament\Articles\Resources\Warehouses\Pages;

use App\Filament\Articles\Resources\Warehouses\WarehouseResource;
use App\Models\Articles\Item;
use App\Models\Articles\StockLocation;
use App\Models\Articles\Warehouse;
use App\Services\Articles\InventoryService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class BinInventoryPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = WarehouseResource::class;

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::Scan;

    protected static ?string $navigationLabel = 'Inventaire par emplacement';

    protected static ?string $title = 'Inventaire physique par emplacement';

    protected static string|null|\UnitEnum $navigationGroup = 'Configuration Dépôts';

    protected static ?int $navigationSort = 20;

    public ?string $warehouseName = null;

    public array $binData = [];

    public function mount(int|string $record): void
    {
        $warehouse = Warehouse::findOrFail($record);
        $this->warehouseName = $warehouse->name;

        $locations = StockLocation::whereHas('stock', fn ($q) => $q->where('warehouse_id', $warehouse->id))
            ->with('stock.item')
            ->hasQuantity()
            ->orderBy('location_code')
            ->get();

        $this->binData = $locations->map(function ($loc) {
            return [
                'id' => $loc->id,
                'location_code' => $loc->location_code,
                'item_reference' => $loc->stock->item->reference,
                'item_name' => $loc->stock->item->name,
                'unit_symbol' => $loc->stock->item->unit?->symbol ?? '',
                'theoretical_quantity' => $loc->quantity,
                'counted_quantity' => null,
                'stock_id' => $loc->stock_id,
                'item_id' => $loc->stock->item_id,
            ];
        })->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Inventaire physique — {$this->warehouseName}")
                    ->description('Comptez la quantité physique pour chaque emplacement. Laissez vide si inchangé.')
                    ->schema([
                        ...collect($this->binData)->map(fn ($bin, $index) => TextInput::make("bins.{$index}.counted_quantity")
                            ->label("{$bin['location_code']} — {$bin['item_reference']}")
                            ->helperText("{$bin['item_name']} (théorique: {$bin['theoretical_quantity']} {$bin['unit_symbol']})")
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->suffix($bin['unit_symbol'])
                        )->toArray(),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        if (! isset($data['bins'])) {
            Notification::make()->warning()->title('Aucune modification')->send();

            return;
        }

        $count = 0;

        foreach ($data['bins'] as $index => $binData) {
            $bin = $this->binData[$index] ?? null;

            if (! $bin || $binData['counted_quantity'] === null || $binData['counted_quantity'] == $bin['theoretical_quantity']) {
                continue;
            }

            $warehouse = Warehouse::where('id', $this->getRecord())->firstOrFail();

            app(InventoryService::class)->reconcile(
                Item::find($bin['item_id']),
                $warehouse,
                $binData['counted_quantity'],
                "Inventaire physique emplacement {$bin['location_code']}",
                $bin['location_code']
            );

            $count++;
        }

        Notification::make()
            ->success()
            ->title('Inventaire validé')
            ->body("{$count} emplacement(s) réconcilié(s).")
            ->send();
    }

    protected function getRecord(): int|string
    {
        return request()->route('record');
    }
}
