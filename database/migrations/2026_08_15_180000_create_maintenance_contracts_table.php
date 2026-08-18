<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->foreignId('third_party_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('client_equipment_id')->constrained('client_equipments')->cascadeOnDelete();
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('frequency')->default('annual');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->decimal('flat_rate_price', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'next_due_date']);
        });

        Schema::create('maintenance_contract_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('maintenance_contracts')->cascadeOnDelete();
            $table->date('due_date');
            $table->integer('days_before');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['contract_id', 'due_date', 'days_before'], 'maintenance_contract_reminders_contract_due_days_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_contract_reminders');
        Schema::dropIfExists('maintenance_contracts');
    }
};
