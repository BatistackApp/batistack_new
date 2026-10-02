<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_condition_reports', function (Blueprint $table): void {
            $table->string('signature_checksum')->nullable()->change();
            $table->dateTime('signed_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_condition_reports', function (Blueprint $table): void {
            $table->string('signature_checksum')->nullable(false)->change();
            $table->dateTime('signed_at')->nullable(false)->change();
        });
    }
};
