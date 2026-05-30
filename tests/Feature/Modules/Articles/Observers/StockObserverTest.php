<?php

namespace Tests\Feature\Modules\Articles\Observers;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Company::factory()->create();
    Notification::fake();
});

describe('StockObserver - creating()', function () {
    test('rejette si quantity < 0', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();

        expect(function () use ($item, $warehouse) {
            Stock::create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => -5,
            ]);
        })->toThrow(Exception::class, 'négative');
    });

    test('rejette si min_threshold < 0', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();

        expect(function () use ($item, $warehouse) {
            Stock::create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
                'min_threshold' => -5,
            ]);
        })->toThrow(Exception::class, 'négatif');
    });

    test('rejette si item_id invalide', function () {
        $warehouse = Warehouse::factory()->create();

        expect(function () use ($warehouse) {
            Stock::create([
                'item_id' => 9999,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('rejette si warehouse_id invalide', function () {
        $item = Item::factory()->create();

        expect(function () use ($item) {
            Stock::create([
                'item_id' => $item->id,
                'warehouse_id' => 9999,
                'quantity' => 10,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('rejette si doublon item/warehouse', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        expect(function () use ($item, $warehouse) {
            Stock::create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 20,
            ]);
        })->toThrow(Exception::class, 'existe déjà');
    });

    test('accepte stock valide', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $stock = Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        expect($stock)->not->toBeNull()
            ->and($stock->quantity)->toBe(50);
    });
});

describe('StockObserver - saved()', function () {
    test('envoie notification si stock bas', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'min_threshold' => 10,
        ]);

        Notification::assertSentTo($admin, LowStockNotification::class);
    });

    test('n\'envoie pas notification si stock sain', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();
        User::factory()->create(['is_admin' => true]);

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        Notification::assertNothingSent();
    });

    test('invalide cache après création', function () {
        Cache::shouldReceive('forget')
            ->with(\Mockery::any())
            ->atLeast()->once();

        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);
    });

    test('invalide cache après mise à jour quantity', function () {
        Cache::shouldReceive('forget')
            ->with(\Mockery::any())
            ->atLeast()->once();

        $stock = Stock::factory()->create(['quantity' => 10]);

        $stock->update(['quantity' => 5]);
    });
});

describe('StockObserver - deleting()', function () {
    test('empêche suppression si mouvements existent', function () {
        $stock = Stock::factory()->create();
        $stock->mouvements()->create([
            'user_id' => User::factory()->create()->id,
            'type' => 'in',
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);

        expect(function () use ($stock) {
            $stock->delete();
        })->toThrow(Exception::class, 'mouvements');
    });

    test('accepte suppression sans mouvements', function () {
        $stock = Stock::factory()->create();

        $stock->delete();

        expect(Stock::find($stock->id))->toBeNull();
    });
});

describe('StockObserver - Intégration', function () {
    test('workflow complet: créer, modifier, notifier', function () {
        $item = Item::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['is_admin' => true]);

        // Créer stock sain
        $stock = Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        Notification::assertNothingSent();

        // Réduire jusqu'au seuil
        $stock->update(['quantity' => 10]);

        Notification::assertSentTo($user, LowStockNotification::class);
    });

    test('multiple entrepôts pour même article', function () {
        $item = Item::factory()->create();
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse1->id,
            'quantity' => 50,
        ]);

        Stock::create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse2->id,
            'quantity' => 30,
        ]);

        expect(Stock::count())->toBe(2);
    });
});
