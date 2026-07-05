<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerSituationFactory extends Factory
{
    protected $model = CustomerSituation::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->randomNumber(),
            'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
            'total_ht' => $this->faker->randomFloat(2, 100, 10000),
            'retenue_garantie_amount' => $this->faker->randomFloat(2, 0, 500),
            'prorata_amount' => $this->faker->randomFloat(2, 0, 500),
            'periode_start' => Carbon::now()->subDays(30),
            'periode_end' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_order_id' => CustomerOrder::factory(),
            'chantier_id' => Chantier::factory(),
            'responsable_id' => User::factory(),
        ];
    }
}
