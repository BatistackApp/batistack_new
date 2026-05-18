<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_situations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CustomerQuote::class);
            $table->foreignIdFor(Chantier::class);
            $table->integer('number');
            $table->string('status');
            $table->decimal('total_ht');
            $table->decimal('total_ttc');
            $table->decimal('retenue_garantie_amount');
            $table->decimal('prorata_amount');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_situations');
    }
};
