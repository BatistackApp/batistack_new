<?php

namespace Database\Factories\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThirdPartyFactory extends Factory
{
    protected $model = ThirdParty::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' '.$this->faker->companySuffix(),
            'type' => $this->faker->randomElement(ThirdPartyType::cases()),
            'siren' => $this->faker->numerify('#########'),
            'siret' => $this->faker->numerify('##############'),
            'vat_number' => $this->faker->bothify('FR##########'),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'is_active' => true,
            'payment_terms_days' => $this->faker->randomElement([0, 30, 45, 60]),
            'delivery_delay_days' => $this->faker->randomElement([2, 5, 7, 10]),
            'credit_limit' => $this->faker->randomFloat(2, 5000, 50000),
        ];
    }
}
