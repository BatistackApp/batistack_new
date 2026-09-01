<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('termination_type')->nullable()->after('signature_status');
            $table->text('termination_reason')->nullable()->after('termination_type');
            $table->date('terminated_at')->nullable()->after('termination_reason');
            $table->date('notice_end_date')->nullable()->after('terminated_at');
            $table->decimal('termination_amount', 15, 2)->nullable()->after('notice_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'termination_type',
                'termination_reason',
                'terminated_at',
                'notice_end_date',
                'termination_amount',
            ]);
        });
    }
};
