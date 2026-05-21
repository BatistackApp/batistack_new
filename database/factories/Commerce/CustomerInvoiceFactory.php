<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInvoiceFactory extends Factory
{
    protected $model = CustomerInvoice::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory()->state(['type' => 'client']),
            'reference' => 'FACT-'.now()->year.'-'.$this->faker->unique()->numberBetween(10000, 99999),
            'type' => $this->faker->randomElement(InvoiceType::cases()),
            'status' => $this->faker->randomElement(InvoiceStatus::cases()),
            'total_ht' => $this->faker->randomFloat(2, 1000, 50000),
            'responsable_id' => User::factory(),
        ];
    }
}
