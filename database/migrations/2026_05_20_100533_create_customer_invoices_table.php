<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'client_id')->constrained();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained();
            $table->foreignIdFor(CustomerOrder::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(CustomerSituation::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'responsable_id')->constrained();
            $table->string('reference')->unique();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->text('signature_hash')->nullable();
            $table->decimal('total_ht', 15)->default(0);
            $table->decimal('total_ttc', 15)->default(0);
            $table->timestamp('due_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
