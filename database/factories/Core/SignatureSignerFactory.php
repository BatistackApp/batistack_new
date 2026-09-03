<?php

namespace Database\Factories\Core;

use App\Enums\Core\SignatureStatus;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SignatureSignerFactory extends Factory
{
    protected $model = SignatureSigner::class;

    public function definition(): array
    {
        return [
            'signature_id' => Signature::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'user_id' => null,
            'role' => $this->faker->randomElement(['Signataire', 'Client', 'Manager', 'Comptable']),
            'status' => SignatureStatus::PENDING,
            'token' => Str::uuid()->toString(),
            'signature_data' => null,
            'ip_address' => null,
            'signed_at' => null,
            'metadata' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => SignatureStatus::PENDING,
            'signed_at' => null,
            'signature_data' => null,
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn () => [
            'status' => SignatureStatus::SIGNED,
            'signed_at' => now(),
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function refused(): static
    {
        return $this->state(fn () => [
            'status' => SignatureStatus::REFUSED,
        ]);
    }
}
