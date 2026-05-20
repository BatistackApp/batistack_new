<?php

namespace Database\Factories\Commerce;

use App\Models\Commerce\CustomerOrderItem;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\CustomerSituationItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerSituationItemFactory extends Factory
{
    protected $model = CustomerSituationItem::class;

    public function definition(): array
    {
        return [
            'porogess_percentage' => $this->faker->randomNumber(),
            'amount_ht' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_situation_id' => CustomerSituation::factory(),
            'customer_order_item_id' => CustomerOrderItem::factory(),
        ];
    }
}
