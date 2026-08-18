<?php

use App\Models\Immobilisation\FixedAsset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('chantier_id')->constrained('chantiers')->cascadeOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('released_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['fixed_asset_id', 'released_at']);
        });

        // Backfill : ouvre un enregistrement pour chaque immo actuellement affectée
        FixedAsset::query()
            ->whereNotNull('chantier_id')
            ->get()
            ->each(function (FixedAsset $asset) {
                $asset->assignments()->create([
                    'chantier_id' => $asset->chantier_id,
                    'assigned_at' => $asset->updated_at ?? now(),
                    'released_at' => null,
                    'assigned_by' => null,
                    'reason' => 'Affectation initiale',
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_assignments');
    }
};
