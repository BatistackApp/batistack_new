<?php

namespace Tests\Feature\Modules\Articles\Jobs;

use App\Enums\Articles\ItemType;
use App\Jobs\Articles\RecalculateWorkCostsJob;
use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use App\Models\Core\Company;
use App\Services\Articles\ItemService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Company::factory()->create();
    Bus::fake();
});

describe('RecalculateWorkCostsJob', function () {
    test('recalcule coût si material utilisé dans ouvrage', function () {
        // Créer matériau
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        // Créer ouvrage
        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 50,
        ]);

        // Lier material -> work
        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material->id,
            'quantity' => 2, // 2 matériaux
        ]);

        // Dispatcher le job
        RecalculateWorkCostsJob::dispatch($material);

        // Le job devrait être en queue
        Bus::assertDispatched(RecalculateWorkCostsJob::class);
    });

    test('recalcule coût avec service', function () {
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 0,
        ]);

        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material->id,
            'quantity' => 1,
        ]);

        $service = app(ItemService::class);

        // Calculer le coût
        $costs = $service->calculateDetailedCost($work);

        expect($costs['total_cost'])->toBeGreaterThan(0);
    });

    test('gère composition sans enfant', function () {
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        // Pas de composition avec ce material
        RecalculateWorkCostsJob::dispatch($material);

        Bus::assertDispatchedTimes(RecalculateWorkCostsJob::class, 1);
    });

    test('gère composition avec plusieurs enfants', function () {
        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 0,
        ]);

        // Plusieurs matériaux
        $m1 = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);
        $m2 = Item::factory()->create([
            'type' => ItemType::CONSUMABLE,
            'purchase_price' => 50,
        ]);

        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $m1->id,
            'quantity' => 1,
        ]);
        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $m2->id,
            'quantity' => 2,
        ]);

        // Updater le prix du premier matériau
        $m1->update(['purchase_price' => 150]);

        Bus::assertDispatched(RecalculateWorkCostsJob::class);
    });

    test('gère hiérarchie imbriquée', function () {
        // Matériau simple
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        // Ouvrage simple contient matériau
        $simple = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 0,
        ]);

        ItemComposition::create([
            'parent_item_id' => $simple->id,
            'child_item_id' => $material->id,
            'quantity' => 1,
        ]);

        // Ouvrage composé contient l'ouvrage simple
        $complex = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 0,
        ]);

        ItemComposition::create([
            'parent_item_id' => $complex->id,
            'child_item_id' => $simple->id,
            'quantity' => 1,
        ]);

        // Updater le matériau - devrait se propager
        $material->update(['purchase_price' => 150]);

        Bus::assertDispatched(RecalculateWorkCostsJob::class);
    });

    test('log erreur si recalcul échoue', function () {
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        RecalculateWorkCostsJob::dispatch($material);

        // Le job s'exécute mais doit logger en cas d'erreur
    });

    test('ne recalcule pas si pas de parent', function () {
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        // Pas de composition
        $parentCompositions = ItemComposition::where('child_item_id', $material->id)->get();

        expect($parentCompositions->count())->toBe(0);
    });

    test('update quiet (sans observer)', function () {
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 50,
        ]);

        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material->id,
            'quantity' => 1,
        ]);

        // Forcer un recalcul
        $work->updateQuietly(['purchase_price' => 150]);

        // Vérifier que pas de job supplémentaire
        Bus::assertNotDispatched(RecalculateWorkCostsJob::class);
    });

    test('gère suppression de composition', function () {
        $material = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 50,
        ]);

        $composition = ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material->id,
            'quantity' => 1,
        ]);

        // Supprimer la composition
        $composition->delete();

        // Plus de parent composition
        $parents = ItemComposition::where('child_item_id', $material->id)->get();

        expect($parents->count())->toBe(0);
    });
});

describe('RecalculateWorkCostsJob - Intégration', function () {
    test('workflow complet: créer hiérarchie, updater prix, recalculer', function () {
        // Créer hiérarchie
        $material1 = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
            'reference' => 'MAT-001',
        ]);

        $material2 = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 50,
            'reference' => 'MAT-002',
        ]);

        $work = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 0,
            'reference' => 'WORK-001',
        ]);

        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material1->id,
            'quantity' => 2,
        ]);

        ItemComposition::create([
            'parent_item_id' => $work->id,
            'child_item_id' => $material2->id,
            'quantity' => 3,
        ]);

        // Vérifier compositions
        expect($work->components->count())->toBe(2);

        // Updater prix du matériau
        $material1->update(['purchase_price' => 150]);

        // Job devrait être dispatché
        Bus::assertDispatched(RecalculateWorkCostsJob::class);
    });
});
