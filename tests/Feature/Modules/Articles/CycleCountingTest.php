<?php

use App\Enums\Articles\InventoryCycleLineStatus;
use App\Enums\Articles\InventoryCycleStatus;
use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\InventoryCycle;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\User;
use App\Services\Articles\CycleCountingService;

beforeEach(function () {
    $this->warehouse = Warehouse::factory()->create();
    $this->service = app(CycleCountingService::class);

    // Seed 15 items with stock
    for ($i = 0; $i < 15; $i++) {
        $item = Item::factory()->create();
        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);
    }
});

it('can generate random cycle count', function () {
    $cycle = $this->service->generateCycle($this->warehouse, 5);

    expect($cycle)->toBeInstanceOf(InventoryCycle::class)
        ->and($cycle->status)->toBe(InventoryCycleStatus::PENDING)
        ->and($cycle->lines)->toHaveCount(5)
        ->and((float) $cycle->lines->first()->theoretical_quantity)->toEqual(100.0);
});

it('can submit and approve cycle count', function () {
    $cycle = $this->service->generateCycle($this->warehouse, 2);

    // Simulate counting
    $line1 = $cycle->lines[0];
    $line1->update(['counted_quantity' => 95, 'status' => InventoryCycleLineStatus::COUNTED]); // 5 missing

    $line2 = $cycle->lines[1];
    $line2->update(['counted_quantity' => 105, 'status' => InventoryCycleLineStatus::COUNTED]); // 5 extra

    $this->service->submitForReview($cycle);
    expect($cycle->fresh()->status)->toBe(InventoryCycleStatus::PENDING_REVIEW);

    $manager = User::factory()->create();
    $this->service->approveCycle($cycle->fresh(), $manager);

    expect($cycle->fresh()->status)->toBe(InventoryCycleStatus::COMPLETED)
        ->and($cycle->fresh()->approved_by)->toBe($manager->id);

    // Assert stock updated
    expect($line1->item->getStockInWarehouse($this->warehouse))->toBe(95.0)
        ->and($line2->item->getStockInWarehouse($this->warehouse))->toBe(105.0);

    // Get the stock instances
    $stock1 = Stock::where('item_id', $line1->item_id)->where('warehouse_id', $this->warehouse->id)->first();
    $stock2 = Stock::where('item_id', $line2->item_id)->where('warehouse_id', $this->warehouse->id)->first();

    // Assert StockMouvement created
    $this->assertDatabaseHas('stock_mouvements', [
        'stock_id' => $stock1->id,
        'quantity_delta' => -5,
        'type' => StockMouvementType::OUT->value,
        'reference_type' => StockMouvementSource::INVENTORY->value,
    ]);

    $this->assertDatabaseHas('stock_mouvements', [
        'stock_id' => $stock2->id,
        'quantity_delta' => 5,
        'type' => StockMouvementType::IN->value,
        'reference_type' => StockMouvementSource::INVENTORY->value,
    ]);
});

it('cannot submit or approve with uncounted lines', function () {
    $cycle = $this->service->generateCycle($this->warehouse, 2);

    // Only count one line
    $line1 = $cycle->lines[0];
    $line1->update(['counted_quantity' => 100, 'status' => InventoryCycleLineStatus::COUNTED]);

    expect(fn () => $this->service->submitForReview($cycle))
        ->toThrow(ArticlesModuleException::class, 'Toutes les lignes doivent être comptées avant soumission.');
});
