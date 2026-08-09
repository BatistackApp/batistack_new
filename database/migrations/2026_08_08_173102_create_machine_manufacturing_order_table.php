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
        Schema::create('machine_manufacturing_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
        });
        
        // Restore machine assignments by picking the first machine linked to each order
        $assignments = \Illuminate\Support\Facades\DB::table('machine_manufacturing_order')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('manufacturing_order_id');
            
        foreach ($assignments as $orderId => $machines) {
            $firstMachine = $machines->first();
            \Illuminate\Support\Facades\DB::table('manufacturing_orders')
                ->where('id', $orderId)
                ->update(['machine_id' => $firstMachine->machine_id]);
                
            if ($machines->count() > 1) {
                \Illuminate\Support\Facades\Log::warning("Rollback of machine_manufacturing_order: ManufacturingOrder {$orderId} had multiple machines. Only machine {$firstMachine->machine_id} was restored.");
            }
        }

        Schema::dropIfExists('machine_manufacturing_order');
    }
};
