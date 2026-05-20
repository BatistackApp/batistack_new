<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Enums\Commerce\PaymentType;
use App\Models\Commerce\Payment;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'third_party_id' => ThirdParty::factory(),
            'reference' => 'TRX-'.$this->faker->unique()->numberBetween(100000, 999999),
            'type' => $this->faker->randomElement(PaymentType::cases()),
            'method' => $this->faker->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::COMPLETED,
            'amount' => $this->faker->randomFloat(2, 100, 15000),
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
