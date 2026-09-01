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
        Schema::table('machine_maintenance_tickets', function (Blueprint $table) {
            $table->decimal('cost_ht', 10, 2)->nullable()->after('description');
            $table->string('provider_name')->nullable()->after('cost_ht');
            $table->text('notes')->nullable()->after('provider_name');
        });
    }

    public function down(): void
    {
        Schema::table('machine_maintenance_tickets', function (Blueprint $table) {
            $table->dropColumn(['cost_ht', 'provider_name', 'notes']);
        });
    }
};
