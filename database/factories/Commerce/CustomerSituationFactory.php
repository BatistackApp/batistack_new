<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
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
            'total_ht' => $this->faker->word(),
            'retenue_garantie_amount' => $this->faker->word(),
            'prorata_amount' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_order_id' => CustomerOrder::factory(),
            'chantier_id' => Chantier::factory(),
        ];
    }
}
