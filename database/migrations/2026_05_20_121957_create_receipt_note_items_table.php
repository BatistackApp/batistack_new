<?php

use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Commerce\ReceiptNote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ReceiptNote::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PurchaseOrderItem::class)->constrained()->cascadeOnDelete();
            $table->decimal('quantity_received', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_note_items');
    }
};
