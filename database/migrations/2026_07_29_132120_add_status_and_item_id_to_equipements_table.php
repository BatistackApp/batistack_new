<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Articles\Item::class)->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('available');
        });
    }

    public function down(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropColumn(['item_id', 'status']);
        });
    }
};
