<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('maps DXF entities 1:1 with duplicate quote and order lines', function () {
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

    DB::statement('
        UPDATE laser_order_lines
        SET dxf_entities = (
            SELECT ql_numbered.dxf_entities
            FROM (
                SELECT id, laser_order_id, material_id,
                    length_mm, width_mm, thickness_mm, quantity,
                    ROW_NUMBER() OVER (
                        PARTITION BY laser_order_id, material_id,
                            length_mm, width_mm, thickness_mm, quantity
                        ORDER BY id
                    ) AS rn
                FROM laser_order_lines
            ) ol_numbered
            JOIN laser_orders o ON o.id = ol_numbered.laser_order_id
            JOIN (
                SELECT id, laser_quote_id, material_id,
                    length_mm, width_mm, thickness_mm, quantity,
                    dxf_entities,
                    ROW_NUMBER() OVER (
                        PARTITION BY laser_quote_id, material_id,
                            length_mm, width_mm, thickness_mm, quantity
                        ORDER BY id
                    ) AS rn
                FROM laser_quote_lines
                WHERE dxf_entities IS NOT NULL
            ) ql_numbered
                ON ql_numbered.laser_quote_id = o.laser_quote_id
                AND ql_numbered.material_id = ol_numbered.material_id
                AND ql_numbered.length_mm = ol_numbered.length_mm
                AND ql_numbered.width_mm = ol_numbered.width_mm
                AND ql_numbered.thickness_mm = ol_numbered.thickness_mm
                AND ql_numbered.quantity = ol_numbered.quantity
                AND ql_numbered.rn = ol_numbered.rn
            WHERE ol_numbered.id = laser_order_lines.id
        )
        WHERE EXISTS (
            SELECT 1
            FROM laser_orders o
            JOIN laser_quote_lines ql
                ON ql.laser_quote_id = o.laser_quote_id
                AND ql.material_id = laser_order_lines.material_id
                AND ql.length_mm = laser_order_lines.length_mm
                AND ql.width_mm = laser_order_lines.width_mm
                AND ql.thickness_mm = laser_order_lines.thickness_mm
                AND ql.quantity = laser_order_lines.quantity
                AND ql.dxf_entities IS NOT NULL
            WHERE o.id = laser_order_lines.laser_order_id
        )
    ');

    $line1 = DB::table('laser_order_lines')->where('id', $olId1)->first();
    $line2 = DB::table('laser_order_lines')->where('id', $olId2)->first();

    expect($line1->dxf_entities)->not->toBeNull();
    expect($line2->dxf_entities)->not->toBeNull();

    expect($line1->dxf_entities)->toBe($dxfA);
    expect($line2->dxf_entities)->toBe($dxfB);
});

it('handles more order lines than quote lines gracefully', function () {
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

    DB::statement('
        UPDATE laser_order_lines
        SET dxf_entities = (
            SELECT ql_numbered.dxf_entities
            FROM (
                SELECT id, laser_order_id, material_id,
                    length_mm, width_mm, thickness_mm, quantity,
                    ROW_NUMBER() OVER (
                        PARTITION BY laser_order_id, material_id,
                            length_mm, width_mm, thickness_mm, quantity
                        ORDER BY id
                    ) AS rn
                FROM laser_order_lines
            ) ol_numbered
            JOIN laser_orders o ON o.id = ol_numbered.laser_order_id
            JOIN (
                SELECT id, laser_quote_id, material_id,
                    length_mm, width_mm, thickness_mm, quantity,
                    dxf_entities,
                    ROW_NUMBER() OVER (
                        PARTITION BY laser_quote_id, material_id,
                            length_mm, width_mm, thickness_mm, quantity
                        ORDER BY id
                    ) AS rn
                FROM laser_quote_lines
                WHERE dxf_entities IS NOT NULL
            ) ql_numbered
                ON ql_numbered.laser_quote_id = o.laser_quote_id
                AND ql_numbered.material_id = ol_numbered.material_id
                AND ql_numbered.length_mm = ol_numbered.length_mm
                AND ql_numbered.width_mm = ol_numbered.width_mm
                AND ql_numbered.thickness_mm = ol_numbered.thickness_mm
                AND ql_numbered.quantity = ol_numbered.quantity
                AND ql_numbered.rn = ol_numbered.rn
            WHERE ol_numbered.id = laser_order_lines.id
        )
        WHERE EXISTS (
            SELECT 1
            FROM laser_orders o
            JOIN laser_quote_lines ql
                ON ql.laser_quote_id = o.laser_quote_id
                AND ql.material_id = laser_order_lines.material_id
                AND ql.length_mm = laser_order_lines.length_mm
                AND ql.width_mm = laser_order_lines.width_mm
                AND ql.thickness_mm = laser_order_lines.thickness_mm
                AND ql.quantity = laser_order_lines.quantity
                AND ql.dxf_entities IS NOT NULL
            WHERE o.id = laser_order_lines.laser_order_id
        )
    ');

    $line1 = DB::table('laser_order_lines')->where('id', $olId1)->first();
    $line2 = DB::table('laser_order_lines')->where('id', $olId2)->first();

    expect($line1->dxf_entities)->toBe($dxfA);
    expect($line2->dxf_entities)->toBeNull();
});
