<?php

use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\ReceiptNote;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\SupplierScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('returns early if third party is not supplier or subcontractor', function () {
    $client = ThirdParty::factory()->create(['type' => 'client']);
    $service = new SupplierScoringService();
    
    $service->calculateScore($client);
    
    expect($client->supplier_score)->toBeNull();
});

it('returns early if no receipts exist', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    $service = new SupplierScoringService();
    
    $service->calculateScore($supplier);
    
    expect($supplier->supplier_score)->toBeNull();
});

it('calculates perfect score for on-time delivery, perfect quality and no litigations', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => Carbon::now()->addDays(5),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now()->addDays(4), // On time
        'quality_rating' => 5, // Perfect quality
        'has_litigation' => false,
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // Delivery: 50, Quality: 30, Litigation: 20 -> 100
    expect($supplier->supplier_score)->toBe(100);
});

it('calculates penalty for late delivery', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => Carbon::now()->addDays(5)->startOfDay(),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now()->addDays(7)->startOfDay(), // 2 days late
        'quality_rating' => 5,
        'has_litigation' => false,
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // 2 days late => 50 - (2 * 5) = 40 for delivery. 40 + 30 + 20 = 90
    expect($supplier->supplier_score)->toBe(90);
});

it('calculates penalty for poor quality', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => Carbon::now()->addDays(5),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now()->addDays(4),
        'quality_rating' => 3, // Poor quality (3/5)
        'has_litigation' => false,
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // Quality 3/5 => (3/5) * 30 = 18 for quality. 50 + 18 + 20 = 88
    expect($supplier->supplier_score)->toBe(88);
});

it('calculates penalty for litigations', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => Carbon::now()->addDays(5),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now()->addDays(4),
        'quality_rating' => 5,
        'has_litigation' => true, // 1 litigation
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // 1 litigation => 10 points penalty. 50 + 30 + (20 - 10) = 90
    expect($supplier->supplier_score)->toBe(90);
});

it('handles missing delivery expectations', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => null, // No expected date
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now(),
        'quality_rating' => 5,
        'has_litigation' => false,
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // Delivery points fallback to 50 when no receipts have delivery logic
    // Delivery: 50, Quality: 30, Litigation: 20 => 100
    expect($supplier->supplier_score)->toBe(100);
});

it('handles missing quality ratings', function () {
    $supplier = ThirdParty::factory()->create(['type' => 'supplier']);
    
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'expected_delivery_date' => Carbon::now()->addDays(5),
    ]);
    
    ReceiptNote::factory()->create([
        'purchase_order_id' => $order->id,
        'received_at' => Carbon::now()->addDays(4),
        'quality_rating' => null, // No rating
        'has_litigation' => false,
    ]);
    
    $service = new SupplierScoringService();
    $service->calculateScore($supplier);
    
    $supplier->refresh();
    // Quality points fallback to 30 when no receipts have quality rating
    // Delivery: 50, Quality: 30, Litigation: 20 => 100
    expect($supplier->supplier_score)->toBe(100);
});
