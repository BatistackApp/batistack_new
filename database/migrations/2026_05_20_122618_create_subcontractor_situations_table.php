<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontractor_situations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThirdParty::class, 'subcontractor_id')->constrained();
            $table->foreignIdFor(Chantier::class)->constrained();
            $table->foreignIdFor(PurchaseOrder::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->integer('progress_percentage');
            $table->decimal('total_ht', 15);
            $table->decimal('retenue_garantie_amount', 15);
            $table->string('status')->default('submitted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontractor_situations');
    }
};
