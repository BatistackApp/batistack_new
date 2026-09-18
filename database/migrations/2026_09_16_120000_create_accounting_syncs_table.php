<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_syncs', function (Blueprint $table) {
            $table->id();
            $table->morphs('syncable');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['syncable_type', 'syncable_id']);
        });

        DB::table('settings')->updateOrInsert(
            ['key' => 'laser_sales_account'],
            ['value' => '704000', 'group' => 'billing', 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'laser_vat_account'],
            ['value' => '445711', 'group' => 'tax', 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_syncs');
    }
};
