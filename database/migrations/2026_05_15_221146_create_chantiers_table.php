<?php

use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chantiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(ThirdParty::class, 'client_id')->constrained();
            $table->foreignIdFor(Employee::class, 'manager_id')->nullable()->constrained();
            $table->string('reference')->unique();
            $table->string('name');
            $table->string('status')->default('study');
            $table->string('address');
            $table->string('zip_code');
            $table->string('city');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('budget_hours', 15)->default(0);
            $table->decimal('budget_material', 15)->default(0);
            $table->decimal('budget_subcontracting', 15)->default(0);
            $table->decimal('budget_total_ht', 15)->default(0);
            $table->date('start_date_preview')->nullable();
            $table->date('end_date_preview')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantiers');
    }
};
