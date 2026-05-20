<?php

use App\Models\Articles\Item;
use App\Models\Commerce\PurchaseRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PurchaseRequest::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Item::class)->constrained();
            $table->string('name');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
