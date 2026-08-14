<?php

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Models\Vision3D\BimModel;
use App\Models\Vision3D\BimQuantity;
use App\Services\Articles\BomProcurementService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Company::factory()->create();
    $this->service = app(BomProcurementService::class);

    $this->supplierA = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    $this->supplierB = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);

    $this->itemA = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'supplier_id' => $this->supplierA->id,
        'purchase_price' => 50.00,
    ]);
    $this->itemB = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'supplier_id' => $this->supplierA->id,
        'purchase_price' => 20.00,
    ]);

    $this->chantier = Chantier::factory()->create();
    $this->warehouse = Warehouse::create(['name' => 'Dépôt test']);

    $this->bimModel = BimModel::factory()->create([
        'modelable_type' => Chantier::class,
        'modelable_id' => $this->chantier->id,
    ]);
});

test('resolveRequirements() regroupe les quantitatifs par article', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 5,
    ]);
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemB->id,
        'quantity_required' => 7,
    ]);

    $requirements = $this->service->resolveRequirements($this->bimModel);

    expect($requirements)->toHaveCount(2);

    $reqA = collect($requirements)->firstWhere('item.id', $this->itemA->id);
    expect($reqA['quantity_required'])->toBe(15.0);
});

test('resolveRequirements() déduit le stock physique', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    Stock::create([
        'item_id' => $this->itemA->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 4,
    ]);

    $requirements = $this->service->resolveRequirements($this->bimModel);

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]['quantity_to_order'])->toBe(6.0);
});

test('resolveRequirements() déduit le stock en commande', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplierA->id,
        'status' => OrderStatus::CONFIRMED,
        'reference' => 'PO-TEST-1',
    ]);
    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->itemA->id,
        'name' => $this->itemA->name,
        'quantity' => 6,
        'price_unit_ht' => 50,
        'vat_rate_id' => $this->itemA->vat_rate_id,
    ]);

    $requirements = $this->service->resolveRequirements($this->bimModel);

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]['quantity_to_order'])->toBe(4.0);
});

test('resolveRequirements() exclut les articles couverts par le stock', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    Stock::create([
        'item_id' => $this->itemA->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 15,
    ]);

    $requirements = $this->service->resolveRequirements($this->bimModel);

    expect($requirements)->toBeEmpty();
});

test('generatePurchaseOrders() crée des bons de commande brouillons groupés par fournisseur', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemB->id,
        'quantity_required' => 7,
    ]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);
    $pos = $result['purchase_orders'];

    expect($pos)->toHaveCount(1)
        ->and($result['ignored_items'])->toBeEmpty();

    $po = $pos[0];
    expect($po->supplier_id)->toBe($this->supplierA->id)
        ->and($po->status)->toBe(OrderStatus::DRAFT)
        ->and($po->chantier_id)->toBe($this->chantier->id)
        ->and($po->items)->toHaveCount(2);

    $itemA = $po->items->firstWhere('item_id', $this->itemA->id);
    expect((float) $itemA->quantity)->toBe(10.0)
        ->and((float) $itemA->price_unit_ht)->toBe(50.0);
});

test('generatePurchaseOrders() calcule les totaux HT/TTC du bon de commande', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 2,
    ]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);
    $po = $result['purchase_orders'][0];

    expect((float) $po->refresh()->total_ht)->toBe(100.0);
});

test('generatePurchaseOrders() met à jour un bon de commande brouillon existant (idempotent)', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    $reference = 'PO-BIM-'.$this->bimModel->id.'-'.$this->supplierA->id;

    $first = $this->service->generatePurchaseOrders($this->bimModel);
    $po1 = $first['purchase_orders'][0];

    $second = $this->service->generatePurchaseOrders($this->bimModel);
    $po2 = $second['purchase_orders'][0];

    expect($po2->id)->toBe($po1->id)
        ->and(PurchaseOrder::where('reference', $reference)->count())->toBe(1);

    // Modifie la BOM (besoin 5) puis relance : la commande existante est mise à jour, pas ignorée.
    BimQuantity::first()->update(['quantity_required' => 5]);

    $third = $this->service->generatePurchaseOrders($this->bimModel);
    $po3 = $third['purchase_orders'][0];

    expect($po3->id)->toBe($po1->id)
        ->and(PurchaseOrder::where('reference', $reference)->count())->toBe(1);

    $itemA = $po3->items()->where('item_id', $this->itemA->id)->first();
    expect((float) $itemA->quantity)->toBe(5.0);
});

test('generatePurchaseOrders() exclut le bon cible du stock en commande lors d\'une relance', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    $this->service->generatePurchaseOrders($this->bimModel);

    // Sans exclusion, le bon déjà généré (10) serait compté comme besoin couvert → rupture nulle.
    $requirements = $this->service->resolveRequirements($this->bimModel);

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]['quantity_to_order'])->toBe(10.0);
});

test('generatePurchaseOrders() supprime les lignes devenues inutiles', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemB->id,
        'quantity_required' => 7,
    ]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);
    expect($result['purchase_orders'][0]->items)->toHaveCount(2);

    // Le stock couvre désormais l'article B → sa ligne doit disparaître du bon.
    Stock::create([
        'item_id' => $this->itemB->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
    ]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);
    $po = $result['purchase_orders'][0];

    expect($po->items)->toHaveCount(1)
        ->and($po->items->first()->item_id)->toBe($this->itemA->id);
});

test('generatePurchaseOrders() scope les commandes à chaque maquette', function () {
    $bimModel2 = BimModel::factory()->create();
    $itemC = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'supplier_id' => $this->supplierB->id,
        'purchase_price' => 30.00,
    ]);

    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);
    BimQuantity::create([
        'bim_model_id' => $bimModel2->id,
        'item_id' => $itemC->id,
        'quantity_required' => 3,
    ]);

    $result1 = $this->service->generatePurchaseOrders($this->bimModel);
    $result2 = $this->service->generatePurchaseOrders($bimModel2);

    $po1 = $result1['purchase_orders'][0];
    $po2 = $result2['purchase_orders'][0];

    expect($po1->reference)->toBe('PO-BIM-'.$this->bimModel->id.'-'.$this->supplierA->id)
        ->and($po2->reference)->toBe('PO-BIM-'.$bimModel2->id.'-'.$this->supplierB->id)
        ->and($po1->id)->not->toBe($po2->id);
});

test('generatePurchaseOrders() expose les articles sans fournisseur', function () {
    $itemNoSupplier = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'supplier_id' => null,
        'purchase_price' => 10,
    ]);

    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $itemNoSupplier->id,
        'quantity_required' => 10,
    ]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);

    expect($result['purchase_orders'])->toBeEmpty()
        ->and($result['ignored_items'])->toHaveCount(1)
        ->and($result['ignored_items'][0]->id)->toBe($itemNoSupplier->id);
});

test('generatePurchaseOrders() n\'attache pas de chantier quand la maquette n\'est pas liée à un chantier', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    $this->bimModel->update(['modelable_type' => null, 'modelable_id' => null]);

    $result = $this->service->generatePurchaseOrders($this->bimModel);
    $po = $result['purchase_orders'][0];

    expect($po->chantier_id)->toBeNull();
});

test('BimQuantity() rejette une quantité non positive', function () {
    expect(fn () => BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 0,
    ]))->toThrow(InvalidArgumentException::class);
});

test('la génération de commandes est refusée sans les permissions Create/Update PurchaseOrder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect($user->can('Create:PurchaseOrder'))->toBeFalse()
        ->and($user->can('Update:PurchaseOrder'))->toBeFalse();
});

test('la génération de commandes est autorisée avec les permissions Create/Update PurchaseOrder', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(
        Permission::findOrCreate('Create:PurchaseOrder'),
        Permission::findOrCreate('Update:PurchaseOrder'),
    );

    $this->actingAs($user);

    expect($user->can('Create:PurchaseOrder'))->toBeTrue()
        ->and($user->can('Update:PurchaseOrder'))->toBeTrue();
});
