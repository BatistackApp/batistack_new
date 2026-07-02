<?php

use App\Models\Tiers\ThirdParty;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\ReceiptNote;
use App\Services\Tiers\SupplierScoringService;
use App\Enums\Tiers\ThirdPartyType;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\DeliveryStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates perfect score for a supplier', function () {
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => OrderStatus::CONFIRMED,
        'expected_delivery_date' => now()->addDays(2),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'status' => DeliveryStatus::DELIVERED,
        'received_at' => now(), // Delivered before expected date -> 50 pts
        'quality_rating' => 5, // Perfect quality -> 30 pts
        'has_litigation' => false, // No litigation -> 20 pts base
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    expect($supplier->refresh()->supplier_score)->toBe(100);
});

it('calculates penalized score for late delivery and litigation', function () {
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => OrderStatus::CONFIRMED,
        'expected_delivery_date' => now()->subDays(2), // Expected 2 days ago
    ]);
    
    $receipt = ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'status' => DeliveryStatus::DELIVERED,
        'received_at' => now(), // Late by 2 days -> 50 - (2 * 5) = 40 pts
        'quality_rating' => 3, // Quality 3/5 -> 18 pts
        'has_litigation' => true, // 1 litigation -> 10 pts penalty from 20 base -> 10 pts
    ]);
    // Total should be: 40 + 18 + 10 = 68
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    expect($supplier->refresh()->supplier_score)->toBe(68);
});

it('averages scores across multiple receipts', function () {
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    
    $order1 = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'expected_delivery_date' => now()->addDays(2)]);
    $order2 = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'expected_delivery_date' => now()->addDays(2)]);
    
    // Receipt 1: Perfect (50 + 30 + 20 = 100)
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order1->id,
        'status' => DeliveryStatus::DELIVERED,
        'received_at' => now(), 
        'quality_rating' => 5, 
        'has_litigation' => false, 
    ]);

    // Receipt 2: Terrible (Late by 10 days = 0, Quality 1/5 = 6, Litigation = 10 pt penalty)
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order2->id,
        'status' => DeliveryStatus::DELIVERED,
        'received_at' => now()->addDays(12), 
        'quality_rating' => 1, 
        'has_litigation' => true, 
    ]);
    
    // Delays average: (50 + 0) / 2 = 25
    // Quality average: (30 + 6) / 2 = 18
    // Litigation: 1 penalty -> 20 - 10 = 10
    // Total = 25 + 18 + 10 = 53
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    expect($supplier->refresh()->supplier_score)->toBe(53);
});
