<?php

namespace Database\Factories\Core;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SignatureFactory extends Factory
{
    protected $model = Signature::class;

    public function definition(): array
    {
        return [
            // Utilisation de valeurs aléatoires provenant des Enums
            'status' => $this->faker->randomElement(SignatureStatus::cases()),
            'type' => $this->faker->randomElement(SignatureType::cases()),

            // Données techniques
            'signature_data' => $this->faker->text(),
            'checksum' => $this->faker->sha256(),
            'ip_address' => $this->faker->ipv4(),
            'signed_at' => Carbon::now(),
            'metadata' => ['user_agent' => $this->faker->userAgent()],

            // Les champs polymorphiques seront surchargés lors de l'utilisation de la factory
            // par exemple : Signature::factory()->for($invoice, 'signable')->create()
            'signable_id' => null,
            'signable_type' => null,

            'user_id' => User::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
