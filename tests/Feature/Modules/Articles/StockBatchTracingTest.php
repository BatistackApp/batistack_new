<?php

namespace Tests\Feature\Modules\Articles;

use App\Enums\Articles\ItemType;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Jobs\Articles\CheckExpiringStocksJob;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\User;
use App\Notifications\Articles\StockExpiringNotification;
use App\Services\Articles\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Company::factory()->create();
    $this->stockService = app(StockService::class);
    $this->user = User::factory()->create();
    auth()->setUser($this->user);

    $this->item = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'purchase_price' => 100.00,
        'is_sensitive' => true,
    ]);
    $this->warehouse = Warehouse::factory()->create();
});

describe('Stock - traçabilité des lots et dates de péremption', function () {
    test('Item expose le flag is_sensitive', function () {
        expect($this->item->is_sensitive)->toBeTrue()
            ->and(Item::factory()->create()->refresh()->is_sensitive)->toBeFalse();
    });

    test('Stock::increase() enregistre le lot et la date de péremption', function () {
        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);

        $mouvement = $stock->increase(10, 'Achat', null, null, 'LOT-001', '2026-12-31');

        expect($mouvement->batch_number)->toBe('LOT-001')
            ->and($mouvement->expiration_date->format('Y-m-d'))->toBe('2026-12-31')
            ->and($mouvement->isIncoming())->toBeTrue();
    });

    test('Stock::decrease() enregistre le lot associé à la sortie', function () {
        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        $mouvement = $stock->decrease(4, 'Vente', null, null, 'LOT-001', '2026-12-31');

        expect($mouvement->batch_number)->toBe('LOT-001')
            ->and((float) $mouvement->quantity_delta)->toBe(-4.0)
            ->and($mouvement->isOutgoing())->toBeTrue();
    });

    test('StockService::entry() associe le lot et la date de péremption au mouvement', function () {
        $this->stockService->entry($this->item, $this->warehouse, 10, 100, 'LOT-X', '2026-12-31');

        $mouvement = StockMouvement::where('batch_number', 'LOT-X')->first();

        expect($mouvement)->not->toBeNull()
            ->and($mouvement->expiration_date->format('Y-m-d'))->toBe('2026-12-31')
            ->and((float) $mouvement->quantity_delta)->toBe(10.0);
    });

    test('StockService::createMouvement() en entrée incrémente le stock avec lot', function () {
        $this->stockService->createMouvement(
            $this->item,
            $this->warehouse,
            'in',
            15,
            null,
            null,
            null,
            'LOT-Y',
            '2026-12-31'
        );

        $stock = Stock::where('item_id', $this->item->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect((float) $stock->quantity)->toBe(15.0)
            ->and($stock->mouvements()->where('batch_number', 'LOT-Y')->exists())->toBeTrue();
    });

    test('StockService::createMouvement() en sortie décrémente le stock', function () {
        $standardItem = Item::factory()->create(['type' => ItemType::STOCKABLE, 'purchase_price' => 100.00]);

        $this->stockService->entry($standardItem, $this->warehouse, 20, 100);
        $this->stockService->createMouvement($standardItem, $this->warehouse, 'out', 8, 'Consommation');

        $stock = Stock::where('item_id', $standardItem->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect((float) $stock->quantity)->toBe(12.0);
    });

    test('StockService::createMouvement() rejette une quantité non positive', function () {
        expect(fn () => $this->stockService->createMouvement($this->item, $this->warehouse, 'in', 0, null))
            ->toThrow(ArticlesModuleException::class, 'strictement positive');
    });

    test('StockService::entry() rejette un article sensible sans lot', function () {
        expect(fn () => $this->stockService->entry($this->item, $this->warehouse, 10, 100))
            ->toThrow(ArticlesModuleException::class, 'article sensible');
    });

    test('StockService::exit() rejette une sortie d\'article sensible sans lot', function () {
        $this->stockService->entry($this->item, $this->warehouse, 10, 100, 'LOT-S', '2026-12-31');

        expect(fn () => $this->stockService->exit($this->item, $this->warehouse, 5, 'Sortie'))
            ->toThrow(ArticlesModuleException::class, 'article sensible');
    });

    test('StockService::exit() enregistre le lot pour une sortie d\'article sensible', function () {
        $this->stockService->entry($this->item, $this->warehouse, 10, 100, 'LOT-O', '2026-12-31');
        $this->stockService->exit($this->item, $this->warehouse, 3, 'Sortie', null, null, 'LOT-O', '2026-12-31');

        $stock = Stock::where('item_id', $this->item->id)->where('warehouse_id', $this->warehouse->id)->first();

        expect((float) $stock->quantity)->toBe(7.0);

        $out = $stock->mouvements()->outgoing()->where('batch_number', 'LOT-O')->first();
        expect($out)->not->toBeNull();
    });

    test('getRemainingBatchQuantity() retourne la quantité restante du lot', function () {
        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);

        $stock->increase(50, 'Achat', null, null, 'LOT-Z', '2026-12-31');
        $stock->decrease(20, 'Vente', null, null, 'LOT-Z', '2026-12-31');

        expect(StockMouvement::getRemainingBatchQuantity($stock->id, 'LOT-Z'))->toBe(30.0);
    });

    test('getRemainingBatchQuantity() retourne 0 quand le lot est entièrement consommé', function () {
        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);

        $stock->increase(10, 'Achat', null, null, 'LOT-FULL', '2026-12-31');
        $stock->decrease(10, 'Vente', null, null, 'LOT-FULL', '2026-12-31');

        expect(StockMouvement::getRemainingBatchQuantity($stock->id, 'LOT-FULL'))->toBe(0.0);
    });

    test('getRemainingBatchQuantity() ignore les autres lots', function () {
        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);

        $stock->increase(10, 'Achat', null, null, 'LOT-A1', '2026-12-31');

        expect(StockMouvement::getRemainingBatchQuantity($stock->id, 'LOT-OTHER'))->toBe(0.0);
    });
});

describe('CheckExpiringStocksJob', function () {
    test('notifie les admins des lots expirant sous 30 jours et encore en stock', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->stockService->entry(
            $this->item,
            $this->warehouse,
            10,
            100,
            'LOT-EXP',
            Carbon::today()->addDays(10)->format('Y-m-d')
        );

        (new CheckExpiringStocksJob)->handle();

        Notification::assertSentTo(
            $admin,
            StockExpiringNotification::class,
            fn ($notification) => $notification->mouvement->batch_number === 'LOT-EXP'
                && $notification->remainingQuantity === 10.0
        );
    });

    test('ne notifie pas quand le lot est entièrement consommé', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $stock = Stock::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);
        $expiration = Carbon::today()->addDays(10)->format('Y-m-d');
        $stock->increase(10, 'Achat', null, null, 'LOT-GONE', $expiration);
        $stock->decrease(10, 'Vente', null, null, 'LOT-GONE', $expiration);

        (new CheckExpiringStocksJob)->handle();

        Notification::assertNotSentTo($admin, StockExpiringNotification::class);
    });

    test('ignore les lots qui n\'expirent pas sous 30 jours', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->stockService->entry(
            $this->item,
            $this->warehouse,
            10,
            100,
            'LOT-LOIN',
            Carbon::today()->addDays(60)->format('Y-m-d')
        );

        (new CheckExpiringStocksJob)->handle();

        Notification::assertNotSentTo($admin, StockExpiringNotification::class);
    });

    test('ne notifie qu\'une seule fois par lot', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->stockService->entry($this->item, $this->warehouse, 10, 100, 'LOT-DUP', Carbon::today()->addDays(5)->format('Y-m-d'));
        $this->stockService->entry($this->item, $this->warehouse, 5, 100, 'LOT-DUP', Carbon::today()->addDays(5)->format('Y-m-d'));

        (new CheckExpiringStocksJob)->handle();

        Notification::assertSentToTimes($admin, StockExpiringNotification::class, 1);
    });
});
