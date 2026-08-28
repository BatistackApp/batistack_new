<?php

use App\Models\Articles\Warehouse;
use App\Models\Commerce\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PurchaseOrder::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->nullable()->constrained();
            $table->string('reference');
            $table->string('status')->default('delivered');
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_notes');
    }
};
