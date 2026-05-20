<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerQuoteItem;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerQuoteItemFactory extends Factory
{
    protected $model = CustomerQuoteItem::class;

    public function definition(): array
    {
        return [
            'lot_label' => $this->faker->word(),
            'name' => $this->faker->name(),
            'quantity' => $this->faker->randomFloat(),
            'purchase_price' => $this->faker->randomFloat(),
            'selling_price' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_quote_id' => CustomerQuote::factory(),
            'item_id' => Item::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
