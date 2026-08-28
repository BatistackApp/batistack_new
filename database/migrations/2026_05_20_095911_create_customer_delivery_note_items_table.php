<?php

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerDeliveryNote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CustomerDeliveryNote::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Item::class)->constrained();
            $table->decimal('quantity_delivered', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_delivery_note_items');
    }
};
