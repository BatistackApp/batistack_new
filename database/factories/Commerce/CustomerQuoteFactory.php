<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerQuoteFactory extends Factory
{
    protected $model = CustomerQuote::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'status' => $this->faker->word(),
            'total_ht' => $this->faker->randomFloat(),
            'total_ttc' => $this->faker->randomFloat(),
            'signed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_id' => ThirdParty::factory(),
            'chantier_id' => Chantier::factory(),
        ];
    }
}
