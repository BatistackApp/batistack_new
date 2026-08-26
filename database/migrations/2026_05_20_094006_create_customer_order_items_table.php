<?php

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerOrder;
use App\Models\Core\VatRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CustomerOrder::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Item::class)->constrained();
            $table->string('name');
            $table->decimal('quantity', 15, 4);
            $table->decimal('selling_price', 15, 4);
            $table->decimal('total_ht', 15, 4)->default(0);
            $table->foreignIdFor(VatRate::class)->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
    }
};
