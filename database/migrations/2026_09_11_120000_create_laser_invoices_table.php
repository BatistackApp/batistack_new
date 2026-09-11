<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('laser_order_id')->constrained('laser_orders')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft');
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->text('signature_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_invoices');
    }
};
