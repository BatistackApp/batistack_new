<?php

use App\Models\Articles\Item;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\VatRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SupplierInvoice::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Item::class)->nullable()->constrained();
            $table->string('name');
            $table->decimal('quantity', 15, 4);
            $table->decimal('price_unit', 15, 4);
            $table->foreignIdFor(VatRate::class)->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_items');
    }
};
