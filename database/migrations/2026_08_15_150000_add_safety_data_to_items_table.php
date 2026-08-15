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
        Schema::table('items', function (Blueprint $table) {
            $table->string('hazard_category')->nullable()->after('is_sensitive');
            $table->json('ghs_pictograms')->nullable()->after('hazard_category');
            $table->json('h_phrases')->nullable()->after('ghs_pictograms');
            $table->json('p_phrases')->nullable()->after('h_phrases');
            $table->timestamp('fds_updated_at')->nullable()->after('p_phrases');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'hazard_category',
                'ghs_pictograms',
                'h_phrases',
                'p_phrases',
                'fds_updated_at',
            ]);
        });
    }
};
