<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'client_id')->constrained();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained();
            $table->foreignIdFor(CustomerOrder::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('preparation');
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_delivery_notes');
    }
};
