<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('laser_quote_id')->nullable()->constrained('laser_quotes')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft');
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->text('terms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_orders');
    }
};
