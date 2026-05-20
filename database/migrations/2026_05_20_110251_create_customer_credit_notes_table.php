<?php

use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'client_id')->constrained();
            $table->foreignIdFor(CustomerInvoice::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'responsable_id')->constrained();
            $table->string('reference')->unique();
            $table->string('status')->default('draft');
            $table->decimal('total_ht', 15)->default(0);
            $table->decimal('total_ttc', 15)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_notes');
    }
};
