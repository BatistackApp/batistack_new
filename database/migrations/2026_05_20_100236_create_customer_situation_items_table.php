<?php

use App\Models\Commerce\CustomerOrderItem;
use App\Models\Commerce\CustomerSituation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_situation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CustomerSituation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CustomerOrderItem::class)->constrained()->cascadeOnDelete();
            $table->integer('porogess_percentage');
            $table->decimal('amount_ht', 15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_situation_items');
    }
};
