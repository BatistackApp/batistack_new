<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Commerce\SupplierInvoiceItem;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SupplierInvoiceItemFactory extends Factory
{
    protected $model = SupplierInvoiceItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'quantity' => $this->faker->randomFloat(),
            'price_unit' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'supplier_invoice_id' => SupplierInvoice::factory(),
            'item_id' => Item::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
