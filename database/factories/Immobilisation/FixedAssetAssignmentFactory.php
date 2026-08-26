<?php

namespace Database\Factories\Immobilisation;

use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Immobilisation\FixedAssetAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAssetAssignment>
 */
class FixedAssetAssignmentFactory extends Factory
{
    protected $model = FixedAssetAssignment::class;

    public function definition(): array
    {
        return [
            'fixed_asset_id' => FixedAsset::factory(),
            'chantier_id' => Chantier::factory(),
            'assigned_at' => now()->subMonth(),
            'released_at' => now(),
            'assigned_by' => null,
            'reason' => null,
        ];
    }
}
