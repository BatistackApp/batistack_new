<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerOrderFactory extends Factory
{
    protected $model = CustomerOrder::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory()->state(['type' => 'client']),
            'chantier_id' => Chantier::factory(),
            'reference' => 'CMD-'.now()->year.'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            'total_ht' => $this->faker->randomFloat(2, 5000, 150000),
            'responsable_id' => User::factory(),
        ];
    }
}
