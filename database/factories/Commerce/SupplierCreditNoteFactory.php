<?php

namespace Database\Factories\Commerce;

use App\Models\Commerce\SupplierCreditNote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SupplierCreditNoteFactory extends Factory
{
    protected $model = SupplierCreditNote::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'status' => $this->faker->word(),
            'total_ht' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'supplier_id' => ThirdParty::factory(),
            'supplier_invoice_id' => SupplierInvoice::factory(),
        ];
    }
}
