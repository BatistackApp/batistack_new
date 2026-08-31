<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chantier_equipment_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_id')->constrained()->cascadeOnDelete();
            $table->morphs('trackable'); // trackable_type + trackable_id (FixedAsset ou Equipement)
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->string('qr_token')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trackable_type', 'trackable_id', 'check_out_at'], 'trackable_presence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantier_equipment_trackings');
    }
};
