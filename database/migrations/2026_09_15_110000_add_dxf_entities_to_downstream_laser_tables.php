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
            DB::statement('
                UPDATE laser_order_lines
                SET dxf_entities = (
                    SELECT ql.dxf_entities
                    FROM laser_quote_lines ql
                    JOIN laser_orders o ON o.laser_quote_id = ql.laser_quote_id
                    WHERE o.id = laser_order_lines.laser_order_id
                        AND ql.material_id = laser_order_lines.material_id
                        AND ql.length_mm = laser_order_lines.length_mm
                        AND ql.width_mm = laser_order_lines.width_mm
                        AND ql.thickness_mm = laser_order_lines.thickness_mm
                        AND ql.quantity = laser_order_lines.quantity
                        AND ql.dxf_entities IS NOT NULL
                    LIMIT 1
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM laser_quote_lines ql
                    JOIN laser_orders o ON o.laser_quote_id = ql.laser_quote_id
                    WHERE o.id = laser_order_lines.laser_order_id
                        AND ql.material_id = laser_order_lines.material_id
                        AND ql.length_mm = laser_order_lines.length_mm
                        AND ql.width_mm = laser_order_lines.width_mm
                        AND ql.thickness_mm = laser_order_lines.thickness_mm
                        AND ql.quantity = laser_order_lines.quantity
                        AND ql.dxf_entities IS NOT NULL
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
        } else {
            DB::statement('
                UPDATE laser_order_lines ol
                JOIN laser_orders o ON o.id = ol.laser_order_id
                JOIN laser_quote_lines ql ON ql.laser_quote_id = o.laser_quote_id
                    AND ql.material_id = ol.material_id
                    AND ql.length_mm = ol.length_mm
                    AND ql.width_mm = ol.width_mm
                    AND ql.thickness_mm = ol.thickness_mm
                    AND ql.quantity = ol.quantity
                SET ol.dxf_entities = ql.dxf_entities
                WHERE ql.dxf_entities IS NOT NULL
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
