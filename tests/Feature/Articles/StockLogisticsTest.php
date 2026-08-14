<?php

namespace Tests\Feature\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Models\User;
use App\Services\Articles\StockLogisticsService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLogisticsTest extends TestCase
{
    use RefreshDatabase;

    private StockLogisticsService $service;
    private User $user;
    private Warehouse $depot;
    private Chantier $chantier;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockLogisticsService::class);
        $this->user = User::factory()->create();
        
        $this->depot = Warehouse::create([
            'name' => 'Dépôt Principal',
            'location' => 'Paris',
            'is_active' => true,
        ]);
        
        $this->chantier = Chantier::factory()->create([
            'name' => 'Chantier Test',
        ]);
        
        $this->item = Item::factory()->create([
            'name' => 'Ciment',
        ]);
        
        // Initialiser du stock au dépôt
        Stock::create([
            'warehouse_id' => $this->depot->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
            'min_threshold' => 10,
        ]);
    }

    public function test_get_or_create_virtual_warehouse()
    {
        $virtual = $this->service->getOrCreateVirtualWarehouse($this->chantier);
        
        $this->assertNotNull($virtual);
        $this->assertEquals($this->chantier->id, $virtual->chantier_id);
        $this->assertStringContainsString($this->chantier->name, $virtual->name);
        
        // Second call should return the same warehouse
        $virtual2 = $this->service->getOrCreateVirtualWarehouse($this->chantier);
        $this->assertEquals($virtual->id, $virtual2->id);
    }

    public function test_transfer_to_chantier()
    {
        $this->service->transferToChantier($this->depot, $this->chantier, $this->item, 30, $this->user->id);
        
        $depotStock = $this->depot->getStockForItem($this->item);
        $this->assertEquals(70, $depotStock);
        
        $virtual = Warehouse::where('chantier_id', $this->chantier->id)->first();
        $chantierStock = $virtual->getStockForItem($this->item);
        $this->assertEquals(30, $chantierStock);
    }

    public function test_transfer_to_chantier_insufficient_stock()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Quantité insuffisante dans l'entrepôt source.");
        
        $this->service->transferToChantier($this->depot, $this->chantier, $this->item, 150, $this->user->id);
    }

    public function test_consume_on_site()
    {
        // Setup : transfer 50
        $this->service->transferToChantier($this->depot, $this->chantier, $this->item, 50, $this->user->id);
        
        // Consume 20
        $this->service->consumeOnSite($this->chantier, $this->item, 20, $this->user->id);
        
        $virtual = Warehouse::where('chantier_id', $this->chantier->id)->first();
        $chantierStock = $virtual->getStockForItem($this->item);
        $this->assertEquals(30, $chantierStock);
    }

    public function test_consume_on_site_insufficient_stock()
    {
        // Setup : transfer 10
        $this->service->transferToChantier($this->depot, $this->chantier, $this->item, 10, $this->user->id);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Quantité insuffisante sur le chantier.");
        
        $this->service->consumeOnSite($this->chantier, $this->item, 20, $this->user->id);
    }

    public function test_return_to_depot()
    {
        // Setup : transfer 40
        $this->service->transferToChantier($this->depot, $this->chantier, $this->item, 40, $this->user->id);
        
        // Consume 10
        $this->service->consumeOnSite($this->chantier, $this->item, 10, $this->user->id);
        
        // Return 30
        $this->service->returnToDepot($this->chantier, $this->depot, $this->item, 30, $this->user->id);
        
        $virtual = Warehouse::where('chantier_id', $this->chantier->id)->first();
        $chantierStock = $virtual->getStockForItem($this->item);
        $this->assertEquals(0, $chantierStock);
        
        $depotStock = $this->depot->getStockForItem($this->item);
        // 100 initial - 40 transfer = 60. Then return 30 = 90.
        $this->assertEquals(90, $depotStock);
    }
}
