<?php

namespace Tests\Feature\Modules\Articles;

use App\Enums\Articles\InventoryCycleLineStatus;
use App\Enums\Articles\InventoryCycleStatus;
use App\Models\Articles\InventoryCycle;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\User;
use App\Services\Articles\CycleCountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleCountingTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;
    protected CycleCountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
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
    }

    public function test_can_generate_random_cycle_count()
    {
        $cycle = $this->service->generateCycle($this->warehouse, 5);

        $this->assertInstanceOf(InventoryCycle::class, $cycle);
        $this->assertEquals(InventoryCycleStatus::PENDING, $cycle->status);
        $this->assertCount(5, $cycle->lines);
        $this->assertEquals(100, $cycle->lines->first()->theoretical_quantity);
    }

    public function test_can_submit_and_approve_cycle_count()
    {
        $cycle = $this->service->generateCycle($this->warehouse, 2);
        
        // Simulate counting
        $line1 = $cycle->lines[0];
        $line1->update(['counted_quantity' => 95]); // 5 missing

        $line2 = $cycle->lines[1];
        $line2->update(['counted_quantity' => 105]); // 5 extra

        $this->service->submitForReview($cycle);
        $this->assertEquals(InventoryCycleStatus::PENDING_REVIEW, $cycle->fresh()->status);

        $manager = User::factory()->create();
        $this->service->approveCycle($cycle->fresh(), $manager);

        $this->assertEquals(InventoryCycleStatus::COMPLETED, $cycle->fresh()->status);
        $this->assertEquals($manager->id, $cycle->fresh()->approved_by);

        // Assert stock updated
        $this->assertEquals(95, $line1->item->getStockInWarehouse($this->warehouse));
        $this->assertEquals(105, $line2->item->getStockInWarehouse($this->warehouse));

        // Get the stock instances
        $stock1 = \App\Models\Articles\Stock::where('item_id', $line1->item_id)->where('warehouse_id', $this->warehouse->id)->first();
        $stock2 = \App\Models\Articles\Stock::where('item_id', $line2->item_id)->where('warehouse_id', $this->warehouse->id)->first();

        // Assert StockMouvement created
        $this->assertDatabaseHas('stock_mouvements', [
            'stock_id' => $stock1->id,
            'quantity_delta' => -5,
            'type' => \App\Enums\Articles\StockMouvementType::OUT->value,
            'reference_type' => \App\Enums\Articles\StockMouvementSource::INVENTORY->value,
        ]);

        $this->assertDatabaseHas('stock_mouvements', [
            'stock_id' => $stock2->id,
            'quantity_delta' => 5,
            'type' => \App\Enums\Articles\StockMouvementType::IN->value,
            'reference_type' => \App\Enums\Articles\StockMouvementSource::INVENTORY->value,
        ]);
    }
}
