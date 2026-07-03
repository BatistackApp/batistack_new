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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('docuseal_template_id')->nullable();
            $table->string('docuseal_submission_id')->nullable();
            $table->string('signature_status')->default('pending'); // pending, sent, signed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['docuseal_template_id', 'docuseal_submission_id', 'signature_status']);
        });
    }
};
