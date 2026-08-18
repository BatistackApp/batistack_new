<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->foreignId('report_template_id')
                ->nullable()
                ->after('maintenance_contract_id')
                ->constrained('intervention_report_templates')
                ->nullOnDelete();

            $table->json('report_data')->nullable()->after('report_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropForeign(['report_template_id']);
            $table->dropColumn(['report_template_id', 'report_data']);
        });
    }
};