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
