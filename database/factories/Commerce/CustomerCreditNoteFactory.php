<?php

namespace Database\Factories\Commerce;

use App\Models\Commerce\CustomerCreditNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerCreditNoteFactory extends Factory
{
    protected $model = CustomerCreditNote::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
            'total_ht' => $this->faker->randomFloat(),
            'total_ttc' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'third_party_id' => ThirdParty::factory(),
            'customer_invoice_id' => CustomerInvoice::factory(),
            'responsable_id' => User::factory(),
        ];
    }
}
