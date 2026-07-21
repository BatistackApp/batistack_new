<?php

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates manufacturing orders and sub-orders when customer order is confirmed', function () {
    \Illuminate\Support\Facades\Bus::fake([
        \App\Jobs\Commerce\GenerateDocumentJob::class,
    ]);

    // 1. Create a parent work item
    $parentWork = Item::factory()->create([
        'type' => ItemType::WORK,
        'name' => 'Armoire sur-mesure',
    ]);
    
    // 2. Create a sub-work item (sub-assembly)
    $subWork = Item::factory()->create([
        'type' => ItemType::WORK,
        'name' => 'Tiroir assemblé',
    ]);
    
    // 3. Create a consumable item
    $consumable = Item::factory()->create([
        'type' => ItemType::CONSUMABLE,
        'name' => 'Vis',
    ]);
    
    // Parent work is composed of 2 subWorks and 10 consumables
    ItemComposition::factory()->create([
        'parent_item_id' => $parentWork->id,
        'child_item_id' => $subWork->id,
        'quantity' => 2,
    ]);
    
    ItemComposition::factory()->create([
        'parent_item_id' => $parentWork->id,
        'child_item_id' => $consumable->id,
        'quantity' => 10,
    ]);

    // 4. Create an order with 1 parentWork and 5 consumables
    $order = CustomerOrder::factory()->create([
        'status' => OrderStatus::DRAFT,
    ]);
    
    CustomerOrderItem::factory()->create([
        'customer_order_id' => $order->id,
        'item_id' => $parentWork->id,
        'quantity' => 3, // Ordered 3 Armoires
    ]);
    
    CustomerOrderItem::factory()->create([
        'customer_order_id' => $order->id,
        'item_id' => $consumable->id,
        'quantity' => 5, // Just ordering some spare screws
    ]);

    // Initially no manufacturing orders
    expect(ManufacturingOrder::count())->toBe(0);

    // 5. Confirm the order
    $order->update(['status' => OrderStatus::CONFIRMED]);

    // 6. Assertions
    // We should have 1 OF for the parentWork (qty 3)
    // and 1 sub-OF for the subWork (qty 3 * 2 = 6)
    // We should NOT have an OF for the consumable
    $ofs = ManufacturingOrder::all();
    expect($ofs->count())->toBe(2);
    
    $parentOf = ManufacturingOrder::where('item_id', $parentWork->id)->first();
    expect($parentOf)->not->toBeNull()
        ->and((float) $parentOf->quantity_planned)->toBe(3.0)
        ->and($parentOf->parent_id)->toBeNull()
        ->and($parentOf->customer_order_id)->toBe($order->id);
        
    $childOf = ManufacturingOrder::where('item_id', $subWork->id)->first();
    expect($childOf)->not->toBeNull()
        ->and((float) $childOf->quantity_planned)->toBe(6.0) // 3 * 2
        ->and($childOf->parent_id)->toBe($parentOf->id)
        ->and($childOf->customer_order_id)->toBe($order->id);
        
    $consumableOf = ManufacturingOrder::where('item_id', $consumable->id)->first();
    expect($consumableOf)->toBeNull();
});
