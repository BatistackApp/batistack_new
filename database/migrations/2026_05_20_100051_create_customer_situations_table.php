<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_situations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CustomerOrder::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantier::class)->constrained();
            $table->foreignIdFor(User::class, 'responsable_id')->constrained();
            $table->integer('number');
            $table->string('status')->default('draft');
            $table->string('total_ht', 15)->default(0);
            $table->string('retenue_garantie_amount', 15)->default(0);
            $table->string('prorata_amount', 15)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_situations');
    }
};
