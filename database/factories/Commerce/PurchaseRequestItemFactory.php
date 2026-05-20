<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\PurchaseRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PurchaseRequestItemFactory extends Factory
{
    protected $model = PurchaseRequestItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'quantity' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'purchase_request_id' => PurchaseRequest::factory(),
            'item_id' => Item::factory(),
        ];
    }
}
