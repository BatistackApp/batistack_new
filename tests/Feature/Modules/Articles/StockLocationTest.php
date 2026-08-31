<?php

use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockLocation;
use App\Models\Articles\Warehouse;
use App\Services\Articles\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockService::class);
    $this->warehouse = Warehouse::create(['name' => 'Dépôt Test', 'is_active' => true]);
    $this->item = Item::factory()->create([
        'is_active' => true,
        'min_stock' => 10,
        'purchase_price' => 50.0,
    ]);
});

describe('StockLocation model', function () {
    test('crée un emplacement avec les bons attributs', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $location = StockLocation::create([
            'stock_id' => $stock->id,
            'location_code' => 'A01-R03-S02-B05',
            'quantity' => 25,
        ]);

        expect($location->stock_id)->toBe($stock->id)
            ->and($location->location_code)->toBe('A01-R03-S02-B05')
            ->and((float) $location->quantity)->toBe(25.0);
    });

    test('la relation stock fonctionne', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $location = StockLocation::create([
            'stock_id' => $stock->id,
            'location_code' => 'BIN-A',
            'quantity' => 20,
        ]);

        expect($location->stock->id)->toBe($stock->id);
    });

    test('la contrainte d\'unicité (stock_id, location_code) fonctionne', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create([
            'stock_id' => $stock->id,
            'location_code' => 'BIN-A',
            'quantity' => 20,
        ]);

        $this->expectException(QueryException::class);

        StockLocation::create([
            'stock_id' => $stock->id,
            'location_code' => 'BIN-A',
            'quantity' => 10,
        ]);
    });

    test('scope hasQuantity retourne uniquement les emplacements avec du stock', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 0]);

        $withQty = StockLocation::hasQuantity()->get();

        expect($withQty)->toHaveCount(1)
            ->and($withQty->first()->location_code)->toBe('A');
    });
});

describe('Stock locations relationship', function () {
    test('locations() retourne les emplacements d\'un stock', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 30]);

        expect($stock->locations)->toHaveCount(2);
    });

    test('getTotalLocationQuantity() calcule la somme', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 30]);

        expect($stock->getTotalLocationQuantity())->toBe(50.0);
    });

    test('getUnallocatedQuantity() calcule la différence', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);

        expect($stock->getUnallocatedQuantity())->toBe(30.0);
    });

    test('isFullyAllocated() retourne vrai quand tout est assigné', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 50]);

        expect($stock->isFullyAllocated())->toBeTrue();
    });
});

describe('StockService::entry with location', function () {
    test('crée un emplacement lors d\'une entrée avec locationCode', function () {
        $this->service->entry($this->item, $this->warehouse, 30, 50.0, null, null, 'A01-R01');

        $stock = Stock::where('item_id', $this->item->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect($stock)->not->toBeNull()
            ->and((float) $stock->quantity)->toBe(30.0);

        $location = $stock->locations()->where('location_code', 'A01-R01')->first();

        expect($location)->not->toBeNull()
            ->and((float) $location->quantity)->toBe(30.0);
    });

    test('incremente un emplacement existant lors d\'une entrée', function () {
        $this->service->entry($this->item, $this->warehouse, 20, 50.0, null, null, 'A01-R01');
        $this->service->entry($this->item, $this->warehouse, 10, 50.0, null, null, 'A01-R01');

        $stock = Stock::where('item_id', $this->item->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect((float) $stock->quantity)->toBe(30.0);

        $location = $stock->locations()->where('location_code', 'A01-R01')->first();

        expect((float) $location->quantity)->toBe(30.0);
    });

    test('l\'agrégat est mis à jour même sans locationCode', function () {
        $this->service->entry($this->item, $this->warehouse, 25, 50.0);

        $stock = Stock::where('item_id', $this->item->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect((float) $stock->quantity)->toBe(25.0)
            ->and($stock->locations)->toHaveCount(0);
    });
});

describe('StockService::exit with location', function () {
    test('sortie FIFO déduit du plus ancien bin en premier', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 30]);

        $this->service->exit($this->item, $this->warehouse, 25);

        $stock->refresh();
        expect((float) $stock->quantity)->toBe(25.0);

        $locA = StockLocation::where('stock_id', $stock->id)->where('location_code', 'A')->first();
        $locB = StockLocation::where('stock_id', $stock->id)->where('location_code', 'B')->first();

        expect((float) $locA->quantity)->toBe(0.0)
            ->and((float) $locB->quantity)->toBe(25.0);
    });

    test('sortie avec bin cible déduit du bon bin', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 30]);

        $this->service->exit($this->item, $this->warehouse, 10, null, null, null, null, null, 'B');

        $locA = StockLocation::where('stock_id', $stock->id)->where('location_code', 'A')->first();
        $locB = StockLocation::where('stock_id', $stock->id)->where('location_code', 'B')->first();

        expect((float) $locA->quantity)->toBe(20.0)
            ->and((float) $locB->quantity)->toBe(20.0);
    });

    test('sortie avec bin cible sans quantité suffisante lève une exception', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 5]);

        $this->expectException(ArticlesModuleException::class);

        $this->service->exit($this->item, $this->warehouse, 10, null, null, null, null, null, 'A');
    });
});

describe('StockService::moveLocation', function () {
    test('déplace du stock entre deux emplacements', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 30]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 20]);

        $this->service->moveLocation($stock, 'A', 'B', 10);

        $locA = StockLocation::where('stock_id', $stock->id)->where('location_code', 'A')->first();
        $locB = StockLocation::where('stock_id', $stock->id)->where('location_code', 'B')->first();

        expect((float) $locA->quantity)->toBe(20.0)
            ->and((float) $locB->quantity)->toBe(30.0);
    });
});

describe('StockService::assignToLocation', function () {
    test('assigne du stock à un emplacement', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $this->service->assignToLocation($stock, 'A01-R01', 25);

        $location = $stock->locations()->where('location_code', 'A01-R01')->first();

        expect($location)->not->toBeNull()
            ->and((float) $location->quantity)->toBe(25.0);
    });

    test('ne peut pas assigner plus que le stock total', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        $this->expectException(ArticlesModuleException::class);

        $this->service->assignToLocation($stock, 'A01-R01', 20);
    });
});

describe('Warehouse model fixes', function () {
    test('getStockForItem retourne la somme de tous les emplacements', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 20]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 30]);

        expect($this->warehouse->getStockForItem($this->item))->toBe(50.0);
    });
});

describe('Item model fixes', function () {
    test('getStockInWarehouse retourne la somme de tous les emplacements', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 40,
        ]);

        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'A', 'quantity' => 15]);
        StockLocation::create(['stock_id' => $stock->id, 'location_code' => 'B', 'quantity' => 25]);

        expect($this->item->getStockInWarehouse($this->warehouse))->toBe(40.0);
    });
});
