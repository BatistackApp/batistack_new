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

        $this->propagateDxfEntities();
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

    private function propagateDxfEntities(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->propagateForSqlite();
        } else {
            $this->propagateForMysql();
        }
    }

    /**
     * Deterministic 1:1 mapping: ROW_NUMBER() is computed independently on quote lines
     * and order lines within each (quote/order, material, dimensions, quantity) group,
     * then matched on group_key + row_number.
     */
    private function propagateForSqlite(): void
    {
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
    }

    private function propagateForMysql(): void
    {
        DB::statement('
            UPDATE laser_order_lines ol
            JOIN (
                SELECT
                    ol_numbered.id AS order_line_id,
                    ql_numbered.dxf_entities
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
            ) AS matched ON matched.order_line_id = ol.id
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
};
