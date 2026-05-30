<?php

namespace Tests\Feature\Modules\Articles\Observers;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Core\Company;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Company::factory()->create();
});

describe('StockMouvementObserver - creating()', function () {
    test('rejette si stock_id invalide', function () {
        $user = User::factory()->create();

        expect(function () use ($user) {
            StockMouvement::create([
                'stock_id' => 9999,
                'user_id' => $user->id,
                'type' => StockMouvementType::IN,
                'quantity_before' => 0,
                'quantity_delta' => 10,
                'quantity_after' => 10,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('rejette si user_id invalide', function () {
        $stock = Stock::factory()->create();

        expect(function () use ($stock) {
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => 9999,
                'type' => StockMouvementType::IN,
                'quantity_before' => 0,
                'quantity_delta' => 10,
                'quantity_after' => 10,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('rejette si before + delta ≠ after', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        expect(function () use ($stock, $user) {
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'type' => StockMouvementType::IN,
                'quantity_before' => 10,
                'quantity_delta' => 5,
                'quantity_after' => 20, // Incorrect: 10+5 ≠ 20
            ]);
        })->toThrow(Exception::class, 'Incohérence');
    });

    test('rejette si type IN avec delta < 0', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        expect(function () use ($stock, $user) {
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'type' => StockMouvementType::IN,
                'quantity_before' => 10,
                'quantity_delta' => -5,
                'quantity_after' => 5,
            ]);
        })->toThrow(Exception::class, 'positive');
    });

    test('rejette si type OUT avec delta > 0', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        expect(function () use ($stock, $user) {
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'type' => StockMouvementType::OUT,
                'quantity_before' => 10,
                'quantity_delta' => 5,
                'quantity_after' => 15,
            ]);
        })->toThrow(Exception::class, 'négative');
    });

    test('accepte mouvement valide IN', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        $mouvement = StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 10,
            'quantity_delta' => 5,
            'quantity_after' => 15,
            'description' => 'Achat',
        ]);

        expect($mouvement)->not->toBeNull()
            ->and((float) $mouvement->quantity_after)->toBe(15.0);
    });

    test('accepte mouvement valide OUT', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        $mouvement = StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::OUT,
            'quantity_before' => 10,
            'quantity_delta' => -5,
            'quantity_after' => 5,
            'description' => 'Vente',
        ]);

        expect($mouvement)->not->toBeNull()
            ->and((float) $mouvement->quantity_after)->toBe(5.0);
    });
});

describe('StockMouvementObserver - created()', function () {
    test('log création', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('Mouvement de stock créé', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Stock créé', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Unit created', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('VatRate created', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Article créé', \Mockery::any());

        Log::shouldReceive('error')->atLeast()->once();

        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);
    });
});

describe('StockMouvementObserver - deleting()', function () {
    test('empêche suppression', function () {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        $mouvement = StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);

        expect(function () use ($mouvement) {
            $mouvement->delete();
        })->toThrow(Exception::class, 'Impossible');
    });
});

describe('StockMouvementObserver - Intégration', function () {
    test('workflow complet: entrée puis sortie', function () {
        $stock = Stock::factory()->create(['quantity' => 10]);
        $user = User::factory()->create();

        // Entrée
        $in = StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 10,
            'quantity_delta' => 20,
            'quantity_after' => 30,
        ]);

        // Sortie
        $out = StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::OUT,
            'quantity_before' => 30,
            'quantity_delta' => -5,
            'quantity_after' => 25,
        ]);

        expect(StockMouvement::count())->toBe(2)
            ->and($in->isIncoming())->toBeTrue()
            ->and($out->isOutgoing())->toBeTrue();
    });

    test('multiple mouvements sur même stock', function () {
        $stock = \App\Models\Articles\Stock::withoutEvents(fn () => Stock::factory()->create());
        $user = User::factory()->create();

        StockMouvement::factory(5)->create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::ADJUSTMENT,
            'reference_type' => StockMouvementSource::INVENTORY,
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);

        expect($stock->mouvements()->count())->toBe(5);
    });

    test('historique cohérent', function () {
        $stock = \App\Models\Articles\Stock::withoutEvents(fn () => Stock::factory()->create(['quantity' => 0]));
        $user = User::factory()->create();

        StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 0,
            'quantity_delta' => 10,
            'quantity_after' => 10,
        ]);

        StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => $user->id,
            'type' => StockMouvementType::IN,
            'quantity_before' => 10,
            'quantity_delta' => 15,
            'quantity_after' => 25,
        ]);

        $mouvements = StockMouvement::orderBy('id')->get();

        expect($mouvements->count())->toBe(2)
            ->and((float) $mouvements[1]->quantity_before)->toBe(10.0)
            ->and((float) $mouvements[1]->quantity_after)->toBe(25.0);
    });
});
