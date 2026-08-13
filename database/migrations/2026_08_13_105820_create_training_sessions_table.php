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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('started_at');
            $table->date('ended_at');
            $table->string('status')->default('planifiee');
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('opco_reimbursement', 10, 2)->default(0);
            $table->string('opco_status')->default('non_demande');
            $table->string('qualification_type')->nullable();
            $table->string('certification_symbol')->nullable();
            $table->integer('validity_months')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_training_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->string('status')->default('inscrit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
