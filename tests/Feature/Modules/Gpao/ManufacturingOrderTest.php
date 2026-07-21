<?php

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Notification::fake();

    // Créer une unité
    $this->unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    // Créer un taux de TVA
    $this->vat = VatRate::create([
        'name' => 'TVA 20%',
        'rate' => 20,
    ]);

    // Créer un entrepôt de base
    $this->warehouse = Warehouse::create([
        'reference' => 'WH-TEST',
        'name' => 'Entrepôt Test',
    ]);

    // Produit fini
    $this->parentItem = Item::create([
        'reference' => 'PF-001',
        'name' => 'Ferme de charpente 5m',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $this->vat->id,
        'purchase_price_ht' => 0,
        'sell_price_ht' => 500,
    ]);

    // Matière première 1
    $this->childItem1 = Item::create([
        'reference' => 'MP-001',
        'name' => 'Poutre Bois 5m',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $this->vat->id,
        'purchase_price_ht' => 20,
    ]);

    // Matière première 2
    $this->childItem2 = Item::create([
        'reference' => 'MP-002',
        'name' => 'Vis acier 100mm',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $this->vat->id,
        'purchase_price_ht' => 0.1,
    ]);

    // Recette (BOM)
    ItemComposition::create([
        'parent_item_id' => $this->parentItem->id,
        'child_item_id' => $this->childItem1->id,
        'quantity' => 2, // 2 poutres
        'loss_percentage' => 10, // 10% de perte
    ]);

    ItemComposition::create([
        'parent_item_id' => $this->parentItem->id,
        'child_item_id' => $this->childItem2->id,
        'quantity' => 20, // 20 vis
        'loss_percentage' => 0,
    ]);
});

it('generates MRP requirements when manufacturing order is created', function () {
    $order = ManufacturingOrder::create([
        'reference' => 'OF-001',
        'item_id' => $this->parentItem->id,
        'quantity_planned' => 5, // On veut fabriquer 5 fermes
        'status' => ManufacturingStatus::DRAFT,
    ]);

    // Le MRP doit s'être déclenché via l'Observer
    expect($order->requirements)->toHaveCount(2);

    $req1 = $order->requirements->where('item_id', $this->childItem1->id)->first();
    // 5 fermes * 2 poutres * 1.10 (perte) = 11 poutres
    expect((float) $req1->quantity_required)->toEqual(11.0);

    $req2 = $order->requirements->where('item_id', $this->childItem2->id)->first();
    // 5 fermes * 20 vis = 100 vis
    expect((float) $req2->quantity_required)->toEqual(100.0);
});

it('consumes inventory when manufacturing order is in progress', function () {
    $order = ManufacturingOrder::create([
        'reference' => 'OF-002',
        'item_id' => $this->parentItem->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::DRAFT,
    ]);

    $order->update(['status' => ManufacturingStatus::IN_PROGRESS]);

    // Vérifier les mouvements de stock OUT
    $movements = StockMouvement::whereHas('stock', function ($q) {
        $q->where('warehouse_id', $this->warehouse->id);
    })
        ->where('type', 'out')
        ->get();

    expect($movements)->toHaveCount(2);
});
