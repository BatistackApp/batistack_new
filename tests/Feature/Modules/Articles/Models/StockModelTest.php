<?php

namespace Tests\Feature\Modules\Articles\Models;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\User;

beforeEach(function () {
    Company::factory()->create();
});

describe('Stock - Scopes', function () {
    test('scope lowStock() filtre stocks bas', function () {
        Stock::factory()->create([
            'quantity' => 5,
            'min_threshold' => 10,
        ]);
        Stock::factory()->create([
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        $lowStock = Stock::lowStock()->get();

        expect($lowStock->count())->toBe(1);
    });

    test('scope critical() filtre stocks critiques', function () {
        Stock::withoutEvents(function () {
            Stock::factory()->create(['quantity' => 0]);
            Stock::factory()->create(['quantity' => -5]);
            Stock::factory()->create(['quantity' => 10]);
        });

        $critical = Stock::critical()->get();

        expect($critical->count())->toBe(2);
    });

    test('scope healthy() filtre stocks sains', function () {
        Stock::factory()->create([
            'quantity' => 100,
            'min_threshold' => 10,
        ]);
        Stock::factory()->create([
            'quantity' => 5,
            'min_threshold' => 10,
        ]);

        $healthy = Stock::healthy()->get();

        expect($healthy->count())->toBe(1);
    });

    test('scope byWarehouse() filtre par entrepôt', function () {
        $w1 = Warehouse::factory()->create();
        $w2 = Warehouse::factory()->create();

        Stock::factory(2)->create(['warehouse_id' => $w1->id]);
        Stock::factory()->create(['warehouse_id' => $w2->id]);

        $result = Stock::byWarehouse($w1)->get();

        expect($result->count())->toBe(2);
    });

    test('scope byItem() filtre par article', function () {
        $item1 = Item::factory()->create();
        $item2 = Item::factory()->create();

        Stock::factory(3)->create(['item_id' => $item1->id]);
        Stock::factory()->create(['item_id' => $item2->id]);

        $result = Stock::byItem($item1)->get();

        expect($result->count())->toBe(3);
    });

    test('scope needsReorder() filtre réapprovisionnement', function () {
        Stock::factory()->create([
            'quantity' => 5,
            'min_threshold' => 10,
        ]);
        Stock::factory()->create([
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        $reorder = Stock::needsReorder()->get();

        expect($reorder->count())->toBe(1);
    });

    test('scope orderByQuantity() trie par quantité', function () {
        Stock::factory()->create(['quantity' => 100]);
        Stock::factory()->create(['quantity' => 10]);
        Stock::factory()->create(['quantity' => 50]);

        $ordered = Stock::orderByQuantity()->get();

        expect($ordered->pluck('quantity')->toArray())->toBe([10, 50, 100]);
    });
});

describe('Stock - Methods Métier', function () {
    test('isLowStock() vérifie si bas', function () {
        $low = Stock::factory()->create([
            'quantity' => 5,
            'min_threshold' => 10,
        ]);
        $healthy = Stock::factory()->create([
            'quantity' => 50,
            'min_threshold' => 10,
        ]);

        expect($low->isLowStock())->toBeTrue()
            ->and($healthy->isLowStock())->toBeFalse();
    });

    test('isCritical() vérifie si critique', function () {
        $critical = Stock::factory()->create(['quantity' => 0]);
        $healthy = Stock::factory()->create(['quantity' => 10]);

        expect($critical->isCritical())->toBeTrue()
            ->and($healthy->isCritical())->toBeFalse();
    });

    test('isHealthy() vérifie si sain', function () {
        $healthy = Stock::factory()->create([
            'quantity' => 100,
            'min_threshold' => 10,
        ]);
        $low = Stock::factory()->create([
            'quantity' => 5,
            'min_threshold' => 10,
        ]);

        expect($healthy->isHealthy())->toBeTrue()
            ->and($low->isHealthy())->toBeFalse();
    });

    test('increase() augmente stock', function () {
        $stock = Stock::factory()->create(['quantity' => 10]);
        $user = User::factory()->create();
        auth()->setUser($user);

        $mouvement = $stock->increase(20, 'Achat');

        expect($stock->fresh()->quantity)->toBe(30)
            ->and((float) $mouvement->quantity_delta)->toBe(20.0);
    });

    test('decrease() diminue stock', function () {
        $stock = Stock::factory()->create(['quantity' => 50]);
        $user = User::factory()->create();
        auth()->setUser($user);

        $mouvement = $stock->decrease(10, 'Vente');

        expect($stock->fresh()->quantity)->toBe(40)
            ->and((float) $mouvement->quantity_delta)->toBe(-10.0);
    });

    test('getRecentMovements() récupère mouvements récents', function () {
        $stock = Stock::factory()->create();

        $stock->mouvements()->createMany([
            ['user_id' => User::factory()->create()->id, 'type' => 'in', 'quantity_before' => 0, 'quantity_delta' => 10, 'quantity_after' => 10],
            ['user_id' => User::factory()->create()->id, 'type' => 'in', 'quantity_before' => 10, 'quantity_delta' => 20, 'quantity_after' => 30],
            ['user_id' => User::factory()->create()->id, 'type' => 'out', 'quantity_before' => 30, 'quantity_delta' => -5, 'quantity_after' => 25],
        ]);

        $recent = $stock->getRecentMovements(2);

        expect($recent->count())->toBe(2);
    });

    test('getLastMovement() récupère dernier mouvement', function () {
        $stock = Stock::factory()->create();

        $stock->mouvements()->create([
            'user_id' => User::factory()->create()->id,
            'type' => 'in',
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);

        sleep(1);

        $stock->mouvements()->create([
            'user_id' => User::factory()->create()->id,
            'type' => 'out',
            'quantity_before' => 10,
            'quantity_delta' => -5,
            'quantity_after' => 5,
        ]);

        $last = $stock->getLastMovement();

        expect($last->type->value)->toBe('out');
    });
});

describe('Stock - Relations', function () {
    test('appartient à Item', function () {
        $item = Item::factory()->create();
        $stock = Stock::factory()->create(['item_id' => $item->id]);

        expect($stock->item->id)->toBe($item->id);
    });

    test('appartient à Warehouse', function () {
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create(['warehouse_id' => $warehouse->id]);

        expect($stock->warehouse->id)->toBe($warehouse->id);
    });

    test('a many mouvements', function () {
        $stock = Stock::factory()->create();

        $stock->mouvements()->createMany([
            ['user_id' => User::factory()->create()->id, 'type' => 'in', 'quantity_before' => 0, 'quantity_delta' => 10, 'quantity_after' => 10],
            ['user_id' => User::factory()->create()->id, 'type' => 'in', 'quantity_before' => 10, 'quantity_delta' => 5, 'quantity_after' => 15],
        ]);

        expect($stock->mouvements->count())->toBe(2);
    });
});

describe('Stock - Intégration', function () {
    test('workflow complet: créer, augmenter, vérifier', function () {
        $stock = Stock::factory()->create(['quantity' => 10, 'min_threshold' => 50]);
        $user = User::factory()->create();
        auth()->setUser($user);

        expect($stock->isLowStock())->toBeTrue();

        $stock->increase(50);

        expect($stock->fresh()->isLowStock())->toBeFalse()
            ->and($stock->item->getTotalStock())->toBe(60.0);
    });

    test('multiple stocks même entrepôt', function () {
        $warehouse = Warehouse::factory()->create();

        Stock::factory(5)->create(['warehouse_id' => $warehouse->id]);

        expect($warehouse->stocks->count())->toBe(5);
    });
});
