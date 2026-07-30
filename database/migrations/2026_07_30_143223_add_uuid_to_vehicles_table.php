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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });
        
        // Generate UUIDs for existing vehicles
        \Illuminate\Support\Facades\DB::table('vehicles')->whereNull('uuid')->get()->each(function ($vehicle) {
            \Illuminate\Support\Facades\DB::table('vehicles')->where('id', $vehicle->id)->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
        });
        
        Schema::table('vehicles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
