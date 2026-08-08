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
        Schema::table('fuel_transactions', function (Blueprint $table) {
            $table->decimal('co2_emission_kg', 10, 2)->nullable()->after('liters')->comment('CO2 emission in kg calculated from fuel type and liters');
        });

        // Backfill existing records
        $transactions = \Illuminate\Support\Facades\DB::table('fuel_transactions')
            ->join('vehicles', 'fuel_transactions.vehicle_id', '=', 'vehicles.id')
            ->select('fuel_transactions.id', 'fuel_transactions.liters', 'vehicles.fuel_type')
            ->get();
            
        $getEmissionFactor = function (?string $fuelType): float {
            $type = mb_strtolower(trim($fuelType ?? ''));
            return match ($type) {
                'diesel', 'gazole', 'b7', 'b10' => 2.64,
                'essence', 'sp95', 'sp98', 'e10', 'hybride' => 2.28,
                'gpl' => 1.66,
                'e85', 'superéthanol' => 0.70,
                'electrique', 'électrique' => 0.0,
                default => 2.28,
            };
        };

        foreach ($transactions as $transaction) {
            \Illuminate\Support\Facades\DB::table('fuel_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'co2_emission_kg' => $transaction->liters * $getEmissionFactor($transaction->fuel_type)
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_transactions', function (Blueprint $table) {
            $table->dropColumn('co2_emission_kg');
        });
    }
};
