<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        return [
            'supplier_id' => ThirdParty::factory()->state(['type' => 'supplier']),
            'reference' => 'RFQ-'.now()->year.'-'.$this->faker->unique()->numberBetween(100, 999),
            'status' => QuoteStatus::SENT,
        ];
    }
}
