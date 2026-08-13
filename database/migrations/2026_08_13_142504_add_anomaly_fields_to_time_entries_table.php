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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->boolean('is_anomaly')->default(false);
            $table->string('anomaly_reason')->nullable();
            $table->timestamp('anomaly_resolved_at')->nullable();
            $table->foreignId('anomaly_resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['anomaly_resolved_by_id']);
            $table->dropColumn(['is_anomaly', 'anomaly_reason', 'anomaly_resolved_at', 'anomaly_resolved_by_id']);
        });
    }
};
