<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('third_parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('type');
            $table->string('siren', 9)->unique()->nullable();
            $table->string('siret', 14)->unique()->nullable();
            $table->string('vat_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->timestamp('last_siren_sync_at')->nullable();
            $table->json('compliant_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('third_parties');
    }
};
