<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'supplier_id')->constrained();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained();
            $table->foreignIdFor(PurchaseRequest::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft');
            $table->decimal('total_ht', 15)->default(0);
            $table->decimal('total_ttc', 15)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
