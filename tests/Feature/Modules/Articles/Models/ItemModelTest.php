<?php

namespace Tests\Feature\Modules\Articles\Models;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Core\Company;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;

beforeEach(function () {
    Company::factory()->create();
});

describe('Item - Relations', function () {
    test('unit relation', function () {
        $unit = Unit::factory()->create();
        $item = Item::factory()->create(['unit_id' => $unit->id]);
        expect($item->unit)->toBeInstanceOf(Unit::class)->and($item->unit->id)->toBe($unit->id);
    });

    test('vatRate relation', function () {
        $vat = VatRate::factory()->create();
        $item = Item::factory()->create(['vat_rate_id' => $vat->id]);
        expect($item->vatRate)->toBeInstanceOf(VatRate::class)->and($item->vatRate->id)->toBe($vat->id);
    });

    test('components relation', function () {
        $parent = Item::factory()->create();
        $parent->components()->create(['child_item_id' => Item::factory()->create()->id, 'quantity' => 1]);
        expect($parent->components)->toHaveCount(1);
    });

    test('stocks relation', function () {
        $item = Item::factory()->create();
        Stock::factory()->create(['item_id' => $item->id]);
        expect($item->stocks)->toHaveCount(1);
    });

    test('parent and children relation', function () {
        $parent = Item::factory()->create();
        $child = Item::factory()->create(['parent_id' => $parent->id]);
        expect($child->parent)->toBeInstanceOf(Item::class)->and($child->parent->id)->toBe($parent->id);
        expect($parent->children)->toHaveCount(1)->and($parent->children->first()->id)->toBe($child->id);
    });

    test('supplier relation', function () {
        $supplier = \App\Models\Tiers\ThirdParty::factory()->create();
        $item = Item::factory()->create(['supplier_id' => $supplier->id]);
        expect($item->supplier)->toBeInstanceOf(\App\Models\Tiers\ThirdParty::class)->and($item->supplier->id)->toBe($supplier->id);
    });
});

describe('Item - Scopes', function () {
    test('scope active() filtre articles actifs', function () {
        Item::factory(2)->create(['is_active' => true]);
        Item::factory(2)->create(['is_active' => false]);

        $active = Item::active()->get();

        expect($active->count())->toBe(2)
            ->and($active->every(fn ($i) => $i->is_active))->toBeTrue();
    });

    test('scope inactive() filtre articles inactifs', function () {
        Item::factory(2)->create(['is_active' => true]);
        Item::factory(2)->create(['is_active' => false]);

        $inactive = Item::inactive()->get();

        expect($inactive->count())->toBe(2)
            ->and($inactive->every(fn ($i) => ! $i->is_active))->toBeTrue();
    });

    test('scope materials() filtre articles matériels', function () {
        Item::factory(3)->create(['type' => ItemType::STOCKABLE]);
        Item::factory(2)->create(['type' => ItemType::LABOR]);

        $materials = Item::materials()->get();

        expect($materials->count())->toBe(3);
    });

    test('scope services() filtre services', function () {
        Item::factory(2)->create(['type' => ItemType::LABOR]);
        Item::factory(3)->create(['type' => ItemType::STOCKABLE]);

        $services = Item::services()->get();

        expect($services->count())->toBe(2);
    });

    test('scope works() filtre ouvrages', function () {
        Item::factory(1)->create(['type' => ItemType::WORK]);
        Item::factory(3)->create(['type' => ItemType::STOCKABLE]);

        $works = Item::works()->get();

        expect($works->count())->toBe(1);
    });

    test('scope search() cherche par référence', function () {
        Item::factory()->create(['reference' => 'ACME-001']);
        Item::factory()->create(['reference' => 'AUTRE-001']);

        $result = Item::search('ACME')->get();

        expect($result->count())->toBe(1);
    });

    test('scope search() cherche par nom', function () {
        Item::factory()->create(['name' => 'Widget']);
        Item::factory()->create(['name' => 'Gadget']);

        $result = Item::search('Widget')->get();

        expect($result->count())->toBe(1);
    });

    test('scope expensive() filtre articles chers', function () {
        Item::factory(2)->create(['selling_price' => 2000]);
        Item::factory(2)->create(['selling_price' => 500]);

        $expensive = Item::expensive(1000)->get();

        expect($expensive->count())->toBe(2);
    });

    test('scope cheap() filtre articles pas chers', function () {
        Item::factory(2)->create(['selling_price' => 50]);
        Item::factory(2)->create(['selling_price' => 500]);

        $cheap = Item::cheap(100)->get();

        expect($cheap->count())->toBe(2);
    });

    test('scope composed() filtre articles composés', function () {
        $parent = Item::factory()->create();
        $simple = Item::factory()->create();

        $parent->components()->create([
            'child_item_id' => Item::factory()->create()->id,
            'quantity' => 1,
        ]);

        $composed = Item::composed()->get();

        expect($composed->count())->toBe(1)
            ->and($composed->first()->id)->toBe($parent->id);
    });

    test('scope orderByName() trie par nom', function () {
        Item::factory()->create(['name' => 'Zebra']);
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Beta']);

        $ordered = Item::orderByName()->get();

        expect($ordered->pluck('name')->toArray())->toBe(['Alpha', 'Beta', 'Zebra']);
    });

    test('scope orderByPrice() trie par prix', function () {
        Item::factory()->create(['selling_price' => 300]);
        Item::factory()->create(['selling_price' => 100]);
        Item::factory()->create(['selling_price' => 200]);

        $ordered = Item::orderByPrice()->get();

        $prices = $ordered->pluck('selling_price')->map(fn ($price) => (float) $price)->toArray();
        expect($prices)->toBe([100.0, 200.0, 300.0]);
    });
    test('scope byType() filtre par type', function () {
        Item::factory(2)->create(['type' => ItemType::STOCKABLE]);
        Item::factory(1)->create(['type' => ItemType::WORK]);
        expect(Item::byType(ItemType::STOCKABLE)->count())->toBe(2);
    });

    test('scope byUnit() filtre par unite', function () {
        $unit = Unit::factory()->create();
        Item::factory(2)->create(['unit_id' => $unit->id]);
        expect(Item::byUnit($unit)->count())->toBe(2);
    });

    test('scope byVatRate() filtre par tva', function () {
        $vat = VatRate::factory()->create();
        Item::factory(2)->create(['vat_rate_id' => $vat->id]);
        expect(Item::byVatRate($vat)->count())->toBe(2);
    });

    test('scope simple() filtre articles sans composants', function () {
        $parent = Item::factory()->create();
        $simple = Item::factory()->create();

        $parent->components()->create([
            'child_item_id' => Item::factory()->create()->id,
            'quantity' => 1,
        ]);

        $simples = Item::simple()->get();
        expect($simples->contains($simple))->toBeTrue()
            ->and($simples->contains($parent))->toBeFalse();
    });
});

describe('Item - Methods Métier', function () {
    test('isComposed() vérifie si article composé', function () {
        $parent = Item::factory()->create();
        $simple = Item::factory()->create();

        $parent->components()->create([
            'child_item_id' => Item::factory()->create()->id,
            'quantity' => 1,
        ]);

        expect($parent->isComposed())->toBeTrue()
            ->and($simple->isComposed())->toBeFalse();
    });

    test('isVariant() vérifie si variante', function () {
        $parent = Item::factory()->create();
        $variant = Item::factory()->create(['parent_id' => $parent->id]);

        expect($variant->isVariant())->toBeTrue()
            ->and($parent->isVariant())->toBeFalse();
    });

    test('isWork() vérifie si ouvrage', function () {
        $work = Item::factory()->create(['type' => ItemType::WORK]);
        $material = Item::factory()->create(['type' => ItemType::STOCKABLE]);

        expect($work->isWork())->toBeTrue()
            ->and($material->isWork())->toBeFalse();
    });

    test('isService() vérifie si service', function () {
        $service = Item::factory()->create(['type' => ItemType::LABOR]);
        $material = Item::factory()->create(['type' => ItemType::STOCKABLE]);

        expect($service->isService())->toBeTrue()
            ->and($material->isService())->toBeFalse();
    });

    test('isMaterial() vérifie si matériel', function () {
        $material = Item::factory()->create(['type' => ItemType::STOCKABLE]);
        $service = Item::factory()->create(['type' => ItemType::LABOR]);

        expect($material->isStockable())->toBeTrue()
            ->and($service->isConsommable())->toBeFalse();
    });

    test('getTotalStock() récupère stock total', function () {
        $item = Item::factory()->create();

        Stock::factory(2)->create([
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        Stock::factory()->create([
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        expect($item->getTotalStock())->toBe(250.0);
    });

    test('isLowStock() vérifie stock bas', function () {
        $item = Item::factory()->create(['min_stock' => 50]);

        Stock::factory()->create([
            'item_id' => $item->id,
            'quantity' => 40,
        ]);

        expect($item->isLowStock())->toBeTrue();
    });

    test('getMargin() calcule marge', function () {
        $item = Item::factory()->create([
            'purchase_price' => 100,
            'selling_price' => 150,
        ]);

        expect($item->getMargin())->toBe(50.0);
    });

    test('getPriceTTC() calcule prix TTC', function () {
        $vat = VatRate::factory()->create(['rate' => 20]);
        $item = Item::factory()->create([
            'selling_price' => 100,
            'vat_rate_id' => $vat->id,
        ]);

        expect($item->getPriceTTC())->toBe(120.0);
    });
});

    test('getStockInWarehouse() recupere stock dun depot', function () {
        $item = Item::factory()->create();
        $warehouse = \App\Models\Articles\Warehouse::factory()->create();
        Stock::factory()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 30,
        ]);

        expect($item->getStockInWarehouse($warehouse))->toBe(30.0);
    });

describe('Item - Static Methods', function () {
    test('byReference() récupère par référence', function () {
        Item::factory()->create(['reference' => 'TEST-001']);

        $item = Item::byReference('TEST-001');

        expect($item)->not->toBeNull()
            ->and($item->reference)->toBe('TEST-001');
    });

    test('referenceExists() vérifie existence', function () {
        Item::factory()->create(['reference' => 'EXISTS-001']);

        expect(Item::referenceExists('EXISTS-001'))->toBeTrue()
            ->and(Item::referenceExists('NOT-FOUND'))->toBeFalse();
    });
});

describe('Item - Intégration', function () {
    test('article avec tous les attributs', function () {
        $unit = Unit::factory()->create();
        $vat = VatRate::factory()->create();

        $item = Item::create([
            'reference' => 'FULL-001',
            'name' => 'Full Item',
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100,
            'selling_price' => 150,
            'unit_id' => $unit->id,
            'vat_rate_id' => $vat->id,
            'min_stock' => 50,
        ]);

        expect($item->isStockable())->toBeTrue()
            ->and($item->getMargin())->toBe(50.0);
    });

    test('workflow: créer, ajouter stock, vérifier', function () {
        $item = Item::factory()->create(['min_stock' => 50]);

        Stock::factory()->create([
            'item_id' => $item->id,
            'quantity' => 40,
        ]);

        expect($item->isLowStock())->toBeTrue()
            ->and($item->getTotalStock())->toBe(40.0);
    });
});
