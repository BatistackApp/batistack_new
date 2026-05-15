<?php

use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->string('brand')->nullable();
            $table->string('model_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('assigned_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('last_check_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
