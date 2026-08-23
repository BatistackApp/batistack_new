<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('dsn_status')->nullable()->after('status');
            $table->datetime('dsn_submitted_at')->nullable()->after('dsn_status');
            $table->datetime('dsn_exported_at')->nullable()->after('dsn_submitted_at');
            $table->text('dsn_error_message')->nullable()->after('dsn_exported_at');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['dsn_status', 'dsn_submitted_at', 'dsn_exported_at', 'dsn_error_message']);
        });
    }
};
