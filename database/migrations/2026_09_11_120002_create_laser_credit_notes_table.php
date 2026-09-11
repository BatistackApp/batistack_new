<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('laser_invoice_id')->constrained('laser_invoices')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('validated');
            $table->decimal('total_ht', 15, 2);
            $table->decimal('total_tva', 15, 2);
            $table->decimal('total_ttc', 15, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_credit_notes');
    }
};
