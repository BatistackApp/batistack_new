<?php

namespace Tests\Feature\Modules\Articles\Observers;

use App\Enums\Articles\ItemType;
use App\Jobs\Articles\RecalculateWorkCostsJob;
use App\Models\Articles\Item;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Company::factory()->create();
    Bus::fake();
});

describe('ItemObserver - creating()', function () {
    test('rejette si référence vide', function () {
        expect(function () {
            Item::create([
                'reference' => '',
                'name' => 'Test Item',
                'type' => ItemType::STOCKABLE,
            ]);
        })->toThrow(Exception::class, 'obligatoire');
    });

    test('rejette si nom vide', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => '',
                'type' => ItemType::STOCKABLE,
            ]);
        })->toThrow(Exception::class, 'obligatoire');
    });

    test('rejette si référence déjà utilisée', function () {
        Item::factory()->create(['reference' => 'ACME-001']);

        expect(function () {
            Item::create([
                'reference' => 'ACME-001',
                'name' => 'Autre',
                'type' => ItemType::STOCKABLE,
            ]);
        })->toThrow(Exception::class, 'déjà utilisée');
    });

    test('rejette si purchase_price < 0', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => 'Test',
                'type' => ItemType::STOCKABLE,
                'purchase_price' => -10,
            ]);
        })->toThrow(Exception::class, 'négatif');
    });

    test('rejette si selling_price < 0', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => 'Test',
                'type' => ItemType::STOCKABLE,
                'selling_price' => -10,
            ]);
        })->toThrow(Exception::class, 'négatif');
    });

    test('rejette si min_stock < 0', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => 'Test',
                'type' => ItemType::STOCKABLE,
                'min_stock' => -5,
            ]);
        })->toThrow(Exception::class, 'négatif');
    });

    test('rejette si unit_id invalide', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => 'Test',
                'type' => ItemType::STOCKABLE,
                'unit_id' => 9999,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('rejette si vat_rate_id invalide', function () {
        expect(function () {
            Item::create([
                'reference' => 'TEST-001',
                'name' => 'Test',
                'type' => ItemType::STOCKABLE,
                'vat_rate_id' => 9999,
            ]);
        })->toThrow(Exception::class, 'n\'existe pas');
    });

    test('accepte article valide', function () {
        $item = Item::create([
            'reference' => 'VALID-001',
            'name' => 'Valid Item',
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
            'selling_price' => 150,
            'unit_id' => Unit::factory()->create()->id,
            'vat_rate_id' => VatRate::factory()->create()->id,
        ]);

        expect($item)->not->toBeNull()
            ->and($item->reference)->toBe('VALID-001');
    });
});

describe('ItemObserver - saving()', function () {
    test('normalise référence en majuscules', function () {
        $item = Item::create([
            'reference' => 'acme-001',
            'name' => 'Test',
            'type' => ItemType::STOCKABLE,
            'unit_id' => Unit::factory()->create()->id,
            'vat_rate_id' => VatRate::factory()->create()->id,
        ]);

        expect($item->reference)->toBe('ACME-001');
    });

    test('normalise nom avec majuscule première lettre', function () {
        $item = Item::create([
            'reference' => 'TEST-001',
            'name' => 'article test',
            'type' => ItemType::STOCKABLE,
            'unit_id' => Unit::factory()->create()->id,
            'vat_rate_id' => VatRate::factory()->create()->id,
        ]);

        expect($item->name)->toBe('Article test');
    });

    test('trim référence et nom', function () {
        $item = Item::create([
            'reference' => '  TEST-001  ',
            'name' => '  Test Item  ',
            'type' => ItemType::STOCKABLE,
            'unit_id' => Unit::factory()->create()->id,
            'vat_rate_id' => VatRate::factory()->create()->id,
        ]);

        expect($item->reference)->toBe('TEST-001')
            ->and($item->name)->toBe('Test Item');
    });
});

describe('ItemObserver - created()', function () {
    test('log création', function () {
        Log::shouldReceive('info')
            ->with('Unit created', \Mockery::any());

        Log::shouldReceive('error')->atLeast()->once();

        Log::shouldReceive('info')
            ->with('VatRate created', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Article créé', \Mockery::any());

        Item::create([
            'reference' => 'LOG-001',
            'name' => 'Test',
            'type' => ItemType::STOCKABLE,
            'unit_id' => Unit::factory()->create()->id,
            'vat_rate_id' => VatRate::factory()->create()->id,
        ]);
    });
});

describe('ItemObserver - updated()', function () {
    test('dispatch RecalculateWorkCostsJob si purchase_price change', function () {
        $item = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
        ]);

        $item->update(['purchase_price' => 150]);

        Bus::assertDispatched(RecalculateWorkCostsJob::class);
    });

    test('ne dispatch pas si type == WORK', function () {
        $item = Item::factory()->create([
            'type' => ItemType::WORK,
            'purchase_price' => 100,
        ]);

        $item->update(['purchase_price' => 150]);

        Bus::assertNotDispatched(RecalculateWorkCostsJob::class);
    });

    test('ne dispatch pas si autre champ change', function () {
        $item = Item::factory()->create();

        $item->update(['name' => 'Nouveau nom']);

        Bus::assertNotDispatched(RecalculateWorkCostsJob::class);
    });

    test('log si selling_price ou is_active changent', function () {
        $item = Item::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Article mis à jour', \Mockery::any());

        $item->update(['selling_price' => 200]);
    });
});

describe('ItemObserver - deleting()', function () {
    test('empêche suppression si en stock', function () {
        $item = Item::factory()->create();
        $item->stocks()->create([
            'warehouse_id' => Warehouse::factory()->create()->id,
            'quantity' => 10,
        ]);

        expect(function () use ($item) {
            $item->delete();
        })->toThrow(Exception::class, 'en stock');
    });

    test('empêche suppression si utilisé dans compositions', function () {
        $item = Item::factory()->create();
        $parent = Item::factory()->create();
        $item->components()->create([
            'parent_item_id' => $parent->id,
            'child_item_id' => $item->id,
            'quantity' => 1,
        ]);

        expect(function () use ($item) {
            $item->delete();
        })->toThrow(Exception::class, 'utilisé');
    });

    test('accepte suppression si non utilisé', function () {
        $item = Item::factory()->create();

        $item->delete();

        expect(Item::find($item->id))->toBeNull();
    });
});
