<?php

namespace Database\Factories\Tiers;

use App\Models\Tiers\ConsultationOffer;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationOfferFactory extends Factory
{
    protected $model = ConsultationOffer::class;

    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'third_party_id' => ThirdParty::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => 'pending',
            'message' => $this->faker->sentence(),
        ];
    }
}
