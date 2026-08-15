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
        Schema::create('chantier_reserves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_id')->constrained('chantiers')->cascadeOnDelete();
            $table->foreignId('chantier_task_id')->nullable()->constrained('chantier_tasks')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('minor');
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->string('lifted_by')->nullable();
            $table->timestamps();

            $table->index(['chantier_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chantier_reserves');
    }
};
