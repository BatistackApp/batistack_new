<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('Signataire');
            $table->string('status')->default('pending');
            $table->string('token', 36)->unique();
            $table->longText('signature_data')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['signature_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_signers');
    }
};
