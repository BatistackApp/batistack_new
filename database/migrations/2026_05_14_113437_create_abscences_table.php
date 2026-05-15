<?php

use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abscences', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('reason')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->dateTime('cibtp_declared_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abscences');
    }
};
