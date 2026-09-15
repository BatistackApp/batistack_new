<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_order_lines', function (Blueprint $table) {
            $table->json('dxf_entities')->nullable()->after('density_kg_m3');
        });

        Schema::table('laser_invoice_lines', function (Blueprint $table) {
            $table->json('dxf_entities')->nullable()->after('density_kg_m3');
        });

        Schema::table('laser_delivery_note_lines', function (Blueprint $table) {
            $table->json('dxf_entities')->nullable()->after('density_kg_m3');
        });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Deterministic 1:1 matching using ROW_NUMBER() partitioned by quote+dimensions,
            // ordered by id to ensure consistent pairing even with duplicate dimension sets.
            DB::statement('
                UPDATE laser_order_lines
                SET dxf_entities = (
                    SELECT ql.dxf_entities
                    FROM (
                        SELECT ol_inner.id AS order_line_id,
                            ql_inner.dxf_entities,
                            ROW_NUMBER() OVER (
                                PARTITION BY ol_inner.laser_order_id, ol_inner.material_id,
                                    ol_inner.length_mm, ol_inner.width_mm,
                                    ol_inner.thickness_mm, ol_inner.quantity
                                ORDER BY ol_inner.id, ql_inner.id
                            ) AS rn
                        FROM laser_order_lines ol_inner
                        JOIN laser_orders o ON o.id = ol_inner.laser_order_id
                        JOIN laser_quote_lines ql_inner
                            ON ql_inner.laser_quote_id = o.laser_quote_id
                            AND ql_inner.material_id = ol_inner.material_id
                            AND ql_inner.length_mm = ol_inner.length_mm
                            AND ql_inner.width_mm = ol_inner.width_mm
                            AND ql_inner.thickness_mm = ol_inner.thickness_mm
                            AND ql_inner.quantity = ol_inner.quantity
                            AND ql_inner.dxf_entities IS NOT NULL
                    ) AS matched
                    WHERE matched.order_line_id = laser_order_lines.id
                        AND matched.rn = 1
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

            // Invoice/delivery note lines have a direct FK (laser_order_line_id) — deterministic by definition.
            DB::statement('
                UPDATE laser_invoice_lines
                SET dxf_entities = (
                    SELECT ol.dxf_entities
                    FROM laser_order_lines ol
                    WHERE ol.id = laser_invoice_lines.laser_order_line_id
                        AND ol.dxf_entities IS NOT NULL
                    LIMIT 1
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM laser_order_lines ol
                    WHERE ol.id = laser_invoice_lines.laser_order_line_id
                        AND ol.dxf_entities IS NOT NULL
                )
            ');

            DB::statement('
                UPDATE laser_delivery_note_lines
                SET dxf_entities = (
                    SELECT ol.dxf_entities
                    FROM laser_order_lines ol
                    WHERE ol.id = laser_delivery_note_lines.laser_order_line_id
                        AND ol.dxf_entities IS NOT NULL
                    LIMIT 1
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM laser_order_lines ol
                    WHERE ol.id = laser_delivery_note_lines.laser_order_line_id
                        AND ol.dxf_entities IS NOT NULL
                )
            ');
        } else {
            // MySQL — deterministic 1:1 matching using ROW_NUMBER()
            DB::statement('
                UPDATE laser_order_lines ol
                JOIN (
                    SELECT ol_inner.id AS order_line_id,
                        ql_inner.dxf_entities,
                        ROW_NUMBER() OVER (
                            PARTITION BY ol_inner.laser_order_id, ol_inner.material_id,
                                ol_inner.length_mm, ol_inner.width_mm,
                                ol_inner.thickness_mm, ol_inner.quantity
                            ORDER BY ol_inner.id, ql_inner.id
                        ) AS rn
                    FROM laser_order_lines ol_inner
                    JOIN laser_orders o ON o.id = ol_inner.laser_order_id
                    JOIN laser_quote_lines ql_inner
                        ON ql_inner.laser_quote_id = o.laser_quote_id
                        AND ql_inner.material_id = ol_inner.material_id
                        AND ql_inner.length_mm = ol_inner.length_mm
                        AND ql_inner.width_mm = ol_inner.width_mm
                        AND ql_inner.thickness_mm = ol_inner.thickness_mm
                        AND ql_inner.quantity = ol_inner.quantity
                        AND ql_inner.dxf_entities IS NOT NULL
                ) AS matched ON matched.order_line_id = ol.id AND matched.rn = 1
                SET ol.dxf_entities = matched.dxf_entities
            ');

            DB::statement('
                UPDATE laser_invoice_lines il
                JOIN laser_order_lines ol ON ol.id = il.laser_order_line_id
                SET il.dxf_entities = ol.dxf_entities
                WHERE ol.dxf_entities IS NOT NULL
            ');

            DB::statement('
                UPDATE laser_delivery_note_lines dl
                JOIN laser_order_lines ol ON ol.id = dl.laser_order_line_id
                SET dl.dxf_entities = ol.dxf_entities
                WHERE ol.dxf_entities IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('laser_order_lines', function (Blueprint $table) {
            $table->dropColumn('dxf_entities');
        });

        Schema::table('laser_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('dxf_entities');
        });

        Schema::table('laser_delivery_note_lines', function (Blueprint $table) {
            $table->dropColumn('dxf_entities');
        });
    }
};
