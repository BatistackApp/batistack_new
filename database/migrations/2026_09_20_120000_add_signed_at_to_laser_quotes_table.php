<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_quotes', function (Blueprint $table): void {
            $table->timestamp('signed_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('laser_quotes', function (Blueprint $table): void {
            $table->dropColumn('signed_at');
        });
    }
};
