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
        Schema::table('third_party_documents', function (Blueprint $table) {
            $table->string('docuseal_submission_id')->nullable()->after('status');
            $table->timestamp('signed_at')->nullable()->after('docuseal_submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('third_party_documents', function (Blueprint $table) {
            $table->dropColumn(['docuseal_submission_id', 'signed_at']);
        });
    }
};
