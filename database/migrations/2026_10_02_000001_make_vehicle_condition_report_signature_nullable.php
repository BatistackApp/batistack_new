<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $unsignedReports = DB::table('vehicle_condition_reports')
            ->where(function ($query): void {
                $query->whereNull('signature_checksum')
                    ->orWhereNull('signed_at');
            })
            ->count();

        if ($unsignedReports > 0) {
            throw new RuntimeException(sprintf(
                'Cannot rollback nullable vehicle signatures: %d report(s) contain NULL signature data. Restore valid signature values before retrying.',
                $unsignedReports
            ));
        }

        Schema::table('vehicle_condition_reports', function (Blueprint $table): void {
            $table->string('signature_checksum')->nullable(false)->change();
            $table->dateTime('signed_at')->nullable(false)->change();
        });
    }
};
