<?php

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\User;
use App\Services\Articles\StockForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockForecastService::class);
    $this->item = Item::factory()->create([
        'is_active' => true,
        'min_stock' => 10,
        'purchase_price' => 50.0,
    ]);
});

function createOutMouvement(Stock $stock, float $delta, Carbon $createdAt): void
{
    $before = (float) $stock->quantity - $delta;
    StockMouvement::create([
        'type' => StockMouvementType::OUT,
        'quantity_before' => $before,
        'quantity_delta' => $delta,
        'quantity_after' => $stock->quantity,
        'description' => 'Sortie test',
        'reference_type' => StockMouvementSource::INTERNAL,
        'reference_id' => 1,
        'stock_id' => $stock->id,
        'user_id' => User::factory()->create()->id,
        'created_at' => $createdAt,
    ]);
}

describe('historicalDailyConsumption', function () {
    test('retourne 0 quand il ny a aucun mouvement de sortie', function () {
        $result = $this->service->historicalDailyConsumption($this->item);

        expect($result)->toBe(0.0);
    });

    test('calcule la moyenne journalière sur 90 jours', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        StockMouvement::create([
            'type' => StockMouvementType::OUT,
            'quantity_before' => 130,
            'quantity_delta' => -30,
            'quantity_after' => 100,
            'description' => 'Sortie test',
            'reference_type' => StockMouvementSource::INTERNAL,
            'reference_id' => 1,
            'stock_id' => $stock->id,
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->service->historicalDailyConsumption($this->item, 90);

        expect(round($result, 2))->toEqual(0.33);
    });
});

describe('seasonalityCoefficient', function () {
    test('retourne 1.0 quand il ny a pas assez de données', function () {
        $result = $this->service->seasonalityCoefficient($this->item);

        expect($result)->toEqual(1.0);
    });

    test('borné entre 0.5 et 1.8', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 200,
        ]);

        $user = User::factory()->create();
        $qty = 200.0;

        for ($i = 0; $i < 40; $i++) {
            $delta = -5.0;
            $before = $qty;
            $qty += $delta;
            StockMouvement::create([
                'type' => StockMouvementType::OUT,
                'quantity_before' => $before,
                'quantity_delta' => $delta,
                'quantity_after' => $qty,
                'description' => 'Sortie test',
                'reference_type' => StockMouvementSource::INTERNAL,
                'reference_id' => 1,
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(rand(1, 700)),
            ]);
        }

        $result = $this->service->seasonalityCoefficient($this->item);

        expect($result)->toBeGreaterThanOrEqual(0.5)
            ->and($result)->toBeLessThanOrEqual(1.8);
    });

    test('retourne 1.0 pour un mois sans données historiques', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 200,
        ]);

        $user = User::factory()->create();
        $qty = 200.0;

        for ($i = 0; $i < 40; $i++) {
            $delta = -5.0;
            $before = $qty;
            $qty += $delta;
            StockMouvement::create([
                'type' => StockMouvementType::OUT,
                'quantity_before' => $before,
                'quantity_delta' => $delta,
                'quantity_after' => $qty,
                'description' => 'Sortie test',
                'reference_type' => StockMouvementSource::INTERNAL,
                'reference_id' => 1,
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'created_at' => now()->subMonths(3)->subDays(rand(1, 28)),
            ]);
        }

        $result = $this->service->seasonalityCoefficient($this->item, (int) now()->month);

        expect($result)->toEqual(1.0);
    });
});

describe('forecast', function () {
    test('retourne daily_burn à 0 et suggested_qty à 0 sans historique', function () {
        $forecast = $this->service->forecast($this->item, 60);

        expect($forecast['daily_burn'])->toEqual(0.0)
            ->and($forecast['suggested_qty'])->toEqual(0.0)
            ->and($forecast['confidence'])->toBe('low');
    });

    test('fallback min_stock quand confiance low et historique vide', function () {
        Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 3,
        ]);

        $forecast = $this->service->forecast($this->item, 60);

        expect($forecast['confidence'])->toBe('low')
            ->and($forecast['suggested_qty'])->toBeGreaterThanOrEqual(0);
    });

    test('calcule jours_until_rupture correctement', function () {
        $stock = Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        StockMouvement::create([
            'type' => StockMouvementType::OUT,
            'quantity_before' => 130,
            'quantity_delta' => -30,
            'quantity_after' => 100,
            'description' => 'Sortie test',
            'reference_type' => StockMouvementSource::INTERNAL,
            'reference_id' => 1,
            'stock_id' => $stock->id,
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subDays(30),
        ]);

        $forecast = $this->service->forecast($this->item, 60);

        expect($forecast['days_until_rupture'])->toBeInt()
            ->and($forecast['days_until_rupture'])->toBeGreaterThan(0);
    });

    test('suggested_qty est toujours >= 0', function () {
        Stock::factory()->create([
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $forecast = $this->service->forecast($this->item, 60);

        expect($forecast['suggested_qty'])->toBeGreaterThanOrEqual(0);
    });

    test('confidence est low, med ou high', function () {
        $forecast = $this->service->forecast($this->item, 60);

        expect($forecast['confidence'])->toBeIn(['low', 'med', 'high']);
    });
});
