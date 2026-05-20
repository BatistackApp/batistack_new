<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerOrderItemFactory extends Factory
{
    protected $model = CustomerOrderItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'quantity' => $this->faker->randomFloat(),
            'selling_price' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_order_id' => CustomerOrder::factory(),
            'item_id' => Item::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
