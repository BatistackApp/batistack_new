<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->decimal('counter_amount', 10, 2)->nullable()->after('total_ttc');
            $table->text('counter_message')->nullable()->after('counter_amount');
        });
    }

    public function down(): void
    {
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->dropColumn(['counter_amount', 'counter_message']);
        });
    }
};
