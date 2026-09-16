<?php

use Illuminate\Support\Facades\DB;

/**
 * Test the propagation migration's actual propagation logic.
 *
 * RefreshDatabase (applied via Pest.php to all Feature tests) already ran all
 * migrations on empty tables — so the schema is correct (dxf_entities columns exist
 * on downstream tables) but nothing was propagated (no data existed).
 *
 * We insert test data AFTER migration, then invoke the real propagation method
 * via reflection to test the ACTUAL code, not a copy of its SQL.
 */
beforeEach(function () {
    // Ensure the propagation tables are clean for this test
    DB::table('laser_delivery_note_lines')->where('dxf_entities', '!=', null)->update(['dxf_entities' => null]);
    DB::table('laser_invoice_lines')->where('dxf_entities', '!=', null)->update(['dxf_entities' => null]);
    DB::table('laser_order_lines')->where('dxf_entities', '!=', null)->update(['dxf_entities' => null]);
});

it('maps DXF entities 1:1 by running the actual migration propagation', function () {
    $clientId = DB::table('third_parties')->insertGetId([
        'name' => 'Test Client',
        'type' => 'customer',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $materialId = DB::table('laser_materials')->insertGetId([
        'name' => 'Acier',
        'is_active' => true,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'min_thickness_mm' => 0.5,
        'max_thickness_mm' => 20.0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quoteId = DB::table('laser_quotes')->insertGetId([
        'client_id' => $clientId,
        'reference' => 'QUOTE-DXF-TEST-001',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('laser_orders')->insertGetId([
        'client_id' => $clientId,
        'laser_quote_id' => $quoteId,
        'reference' => 'ORDER-DXF-TEST-001',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dxfA = json_encode(['entities' => [['type' => 'LINE', 'layer' => 'CUT']]]);
    $dxfB = json_encode(['entities' => [['type' => 'CIRCLE', 'layer' => 'CUT']]]);

    DB::table('laser_quote_lines')->insert([
        'laser_quote_id' => $quoteId,
        'material_id' => $materialId,
        'description' => 'Quote line A',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'dxf_entities' => $dxfA,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('laser_quote_lines')->insert([
        'laser_quote_id' => $quoteId,
        'material_id' => $materialId,
        'description' => 'Quote line B',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'dxf_entities' => $dxfB,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId1 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'description' => 'Order line 1',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId2 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'description' => 'Order line 2',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Invoke the ACTUAL migration's propagation method via reflection
    $migration = require database_path('migrations/2026_09_15_110000_add_dxf_entities_to_downstream_laser_tables.php');
    $reflection = new \ReflectionClass($migration);
    $method = $reflection->getMethod('propagateDxfEntities');
    $method->setAccessible(true);
    $method->invoke($migration);

    $line1 = DB::table('laser_order_lines')->where('id', $olId1)->first();
    $line2 = DB::table('laser_order_lines')->where('id', $olId2)->first();

    expect($line1->dxf_entities)->not->toBeNull();
    expect($line2->dxf_entities)->not->toBeNull();
    expect($line1->dxf_entities)->toBe($dxfA);
    expect($line2->dxf_entities)->toBe($dxfB);
});

it('handles more order lines than quote lines gracefully via actual propagation', function () {
    $clientId = DB::table('third_parties')->insertGetId([
        'name' => 'Test Client 2',
        'type' => 'customer',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $materialId = DB::table('laser_materials')->insertGetId([
        'name' => 'Aluminium',
        'is_active' => true,
        'price_per_kg' => 2.00,
        'price_per_meter' => 2.00,
        'density_kg_m3' => 2700,
        'min_thickness_mm' => 0.5,
        'max_thickness_mm' => 20.0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quoteId = DB::table('laser_quotes')->insertGetId([
        'client_id' => $clientId,
        'reference' => 'QUOTE-DXF-TEST-002',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('laser_orders')->insertGetId([
        'client_id' => $clientId,
        'laser_quote_id' => $quoteId,
        'reference' => 'ORDER-DXF-TEST-002',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dxfA = json_encode(['entities' => [['type' => 'LINE']]]);
    DB::table('laser_quote_lines')->insert([
        'laser_quote_id' => $quoteId,
        'material_id' => $materialId,
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'dxf_entities' => $dxfA,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId1 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId2 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Invoke the ACTUAL migration's propagation method via reflection
    $migration = require database_path('migrations/2026_09_15_110000_add_dxf_entities_to_downstream_laser_tables.php');
    $reflection = new \ReflectionClass($migration);
    $method = $reflection->getMethod('propagateDxfEntities');
    $method->setAccessible(true);
    $method->invoke($migration);

    $line1 = DB::table('laser_order_lines')->where('id', $olId1)->first();
    $line2 = DB::table('laser_order_lines')->where('id', $olId2)->first();

    expect($line1->dxf_entities)->toBe($dxfA);
    expect($line2->dxf_entities)->toBeNull();
});

it('preserves row positions when some quote lines have NULL dxf_entities via actual propagation', function () {
    $clientId = DB::table('third_parties')->insertGetId([
        'name' => 'Test Client 3',
        'type' => 'customer',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $materialId = DB::table('laser_materials')->insertGetId([
        'name' => 'Inox',
        'is_active' => true,
        'price_per_kg' => 3.00,
        'price_per_meter' => 3.00,
        'density_kg_m3' => 8000,
        'min_thickness_mm' => 0.5,
        'max_thickness_mm' => 20.0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quoteId = DB::table('laser_quotes')->insertGetId([
        'client_id' => $clientId,
        'reference' => 'QUOTE-DXF-TEST-003',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('laser_orders')->insertGetId([
        'client_id' => $clientId,
        'laser_quote_id' => $quoteId,
        'reference' => 'ORDER-DXF-TEST-003',
        'status' => 'draft',
        'total_ht' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dxfA = json_encode(['entities' => [['type' => 'LINE', 'layer' => 'CUT']]]);

    // Quote line 1: no DXF — must NOT shift the numbering for quote line 2
    DB::table('laser_quote_lines')->insert([
        'laser_quote_id' => $quoteId,
        'material_id' => $materialId,
        'description' => 'Quote line A (no DXF)',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'dxf_entities' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Quote line 2: has DXF — must map to order line 2 (same rn=2)
    DB::table('laser_quote_lines')->insert([
        'laser_quote_id' => $quoteId,
        'material_id' => $materialId,
        'description' => 'Quote line B (with DXF)',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'dxf_entities' => $dxfA,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId1 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'description' => 'Order line 1',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $olId2 = DB::table('laser_order_lines')->insertGetId([
        'laser_order_id' => $orderId,
        'material_id' => $materialId,
        'description' => 'Order line 2',
        'length_mm' => 100.00,
        'width_mm' => 50.00,
        'thickness_mm' => 5.00,
        'quantity' => 2,
        'cut_length_mm' => 0,
        'price_per_kg' => 1.00,
        'price_per_meter' => 1.00,
        'density_kg_m3' => 7850,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Invoke the ACTUAL migration's propagation method via reflection
    $migration = require database_path('migrations/2026_09_15_110000_add_dxf_entities_to_downstream_laser_tables.php');
    $reflection = new \ReflectionClass($migration);
    $method = $reflection->getMethod('propagateDxfEntities');
    $method->setAccessible(true);
    $method->invoke($migration);

    $line1 = DB::table('laser_order_lines')->where('id', $olId1)->first();
    $line2 = DB::table('laser_order_lines')->where('id', $olId2)->first();

    // Order line 1 (rn=1) must NOT get DXF — quote line 1 has NULL dxf
    expect($line1->dxf_entities)->toBeNull();

    // Order line 2 (rn=2) must get DXF — quote line 2 has DXF and rn=2
    expect($line2->dxf_entities)->toBe($dxfA);
});
