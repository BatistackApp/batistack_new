<?php

use App\Models\Commerce\PurchaseOrder;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'supplier_id')->constrained();
            $table->foreignIdFor(PurchaseOrder::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->decimal('amount_ht', 15);
            $table->decimal('amount_ttc', 15);
            $table->string('status')->default('draft');
            $table->string('dispute_reason')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
