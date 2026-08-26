<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table de l'entreprise
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('siret', 14)->unique()->nullable();
            $table->string('vat_number')->nullable();
            $table->decimal('share_capital', 15, 2)->default(0);
            $table->string('address')->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });

        // Table des unités de mesure
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 10);
            $table->string('type'); // UnitType Enum
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table des taux de TVA (Précision 4 décimales)
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table des paramètres généraux
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Nouveaux champs basés sur les Enums
            $table->string('status')->default('pending');
            $table->string('type')->default('autograph');

            $table->longText('signature_data')->nullable(); // Optionnel si refusé
            $table->string('checksum')->index();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('vat_rates');
        Schema::dropIfExists('units');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('signatures');
    }
};
