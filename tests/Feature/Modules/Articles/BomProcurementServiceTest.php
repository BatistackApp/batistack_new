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
use App\Models\Vision3D\BimModel;
use App\Models\Vision3D\BimQuantity;
use App\Services\Articles\BomProcurementService;

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

    $pos = $this->service->generatePurchaseOrders($this->bimModel);

    expect($pos)->toHaveCount(1);

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

    $po = $this->service->generatePurchaseOrders($this->bimModel)[0];

    expect((float) $po->refresh()->total_ht)->toBe(100.0);
});

test('generatePurchaseOrders() met à jour un bon de commande brouillon existant (idempotent)', function () {
    BimQuantity::create([
        'bim_model_id' => $this->bimModel->id,
        'item_id' => $this->itemA->id,
        'quantity_required' => 10,
    ]);

    $this->service->generatePurchaseOrders($this->bimModel);
    $this->service->generatePurchaseOrders($this->bimModel);

    $count = PurchaseOrder::where('supplier_id', $this->supplierA->id)
        ->where('reference', 'PO-BIM-'.date('Ymd').'-'.$this->supplierA->id)
        ->count();

    expect($count)->toBe(1);
});

test('generatePurchaseOrders() ignore les articles sans fournisseur', function () {
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

    $pos = $this->service->generatePurchaseOrders($this->bimModel);

    expect($pos)->toBeEmpty();
});
