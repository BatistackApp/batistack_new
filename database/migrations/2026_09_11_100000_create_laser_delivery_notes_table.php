<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('laser_order_id')->constrained('laser_orders')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft');
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_delivery_notes');
    }
};
