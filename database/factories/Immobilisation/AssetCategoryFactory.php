<?php

namespace Database\Factories\Immobilisation;

use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amortissementAccounts = ['281100', '281200', '281300', '281500', '281800'];

        return [
            'name' => $this->faker->word().' Category',
            'account_code' => '218'.$this->faker->randomDigitNotNull(),
            'compte_amortissement' => $this->faker->randomElement($amortissementAccounts),
            'default_depreciation_years' => 5,
            'default_method' => DepreciationMethod::LINEAR,
        ];
    }
}
