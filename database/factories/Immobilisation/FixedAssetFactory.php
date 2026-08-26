<?php

namespace Database\Factories\Immobilisation;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_category_id' => AssetCategory::factory(),
            'name' => $this->faker->words(3, true),
            'serial_number' => $this->faker->ean13(),
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'purchase_price' => $this->faker->randomFloat(2, 500, 10000),
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::LINEAR,
            'useful_life_years' => 5,
            'status' => AssetStatus::ACTIVE,
        ];
    }
}
