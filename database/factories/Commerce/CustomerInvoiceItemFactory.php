<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerInvoiceItemFactory extends Factory
{
    protected $model = CustomerInvoiceItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'quantity' => $this->faker->randomFloat(),
            'price_unit' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_invoice_id' => CustomerInvoice::factory(),
            'item_id' => Item::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
