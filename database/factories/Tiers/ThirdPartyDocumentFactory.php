<?php

namespace Database\Factories\Tiers;

use App\Models\Tiers\ThirdPartyDocument;
use App\Models\Tiers\ThirdParty;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThirdPartyDocumentFactory extends Factory
{
    protected $model = ThirdPartyDocument::class;

    public function definition(): array
    {
        return [
            'third_party_id' => ThirdParty::factory(),
            'type' => $this->faker->randomElement(ThirdPartyDocumentType::cases()),
            'status' => $this->faker->randomElement(ThirdPartyDocumentStatus::cases()),
            'expiration_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'docuseal_submission_id' => null,
            'signed_at' => null,
        ];
    }
}
