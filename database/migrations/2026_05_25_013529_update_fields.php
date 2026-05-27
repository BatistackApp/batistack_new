<?php

use App\Models\Commerce\CustomerOrderItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_delivery_note_items', function (Blueprint $table) {
            $table->foreignIdFor(CustomerOrderItem::class)->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('customer_invoice_items', function (Blueprint $table) {
            $table->decimal('total_ht', 8, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('customer_delivery_note_items', function (Blueprint $table) {
            $table->dropColumn('customer_order_item_id');
        });

        Schema::table('customer_invoice_items', function (Blueprint $table) {
            $table->dropColumn('total_ht');
        });
    }
};
