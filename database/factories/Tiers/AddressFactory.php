<?php

namespace Database\Factories\Tiers;

use App\Enums\Tiers\AddressType;
use App\Models\Tiers\Address;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(AddressType::cases()),
            'street' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country' => 'France',
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_default' => false,
        ];
    }
}
