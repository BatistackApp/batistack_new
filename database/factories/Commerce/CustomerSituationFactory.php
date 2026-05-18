<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerSituation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerSituationFactory extends Factory
{
    protected $model = CustomerSituation::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->randomNumber(),
            'status' => $this->faker->word(),
            'total_ht' => $this->faker->randomFloat(),
            'total_ttc' => $this->faker->randomFloat(),
            'retenue_garantie_amount' => $this->faker->randomFloat(),
            'prorata_amount' => $this->faker->randomFloat(),
            'approved_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_quote_id' => CustomerQuote::factory(),
            'chantier_id' => Chantier::factory(),
        ];
    }
}
