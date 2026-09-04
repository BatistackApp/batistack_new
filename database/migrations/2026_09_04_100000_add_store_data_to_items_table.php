<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('store_category')->nullable()->after('type')->index();
            $table->decimal('store_reorder_qty', 10, 2)->default(0)->after('store_category');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['store_category', 'store_reorder_qty']);
        });
    }
};
