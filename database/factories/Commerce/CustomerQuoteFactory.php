<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerQuoteFactory extends Factory
{
    protected $model = CustomerQuote::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory()->state(['type' => 'client']),
            'chantier_id' => Chantier::factory(),
            'reference' => 'DEV-'.now()->year.'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement(QuoteStatus::cases()),
            'total_ht' => $this->faker->randomFloat(2, 5000, 150000),
            'total_ttc' => $this->faker->randomFloat(2, 6000, 180000),
            'responsable_id' => User::factory(),
        ];
    }
}
