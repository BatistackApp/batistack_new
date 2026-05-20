<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition(): array
    {
        return [
            'supplier_id' => ThirdParty::factory()->state(['type' => 'supplier']),
            'purchase_order_id' => PurchaseOrder::factory(),
            'reference' => 'F-FOURN-'.$this->faker->unique()->numberBetween(1000, 9999),
            'amount_ht' => $this->faker->randomFloat(2, 500, 20000),
            'status' => InvoiceStatus::DRAFT,
        ];
    }
}
