<?php

namespace Database\Factories\Banque;

use App\Enums\Banque\BankAccountType;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company() . ' - Compte Principal',
            'type' => $this->faker->randomElement(BankAccountType::cases()),
            'iban' => $this->faker->iban('FR'),
            'bic' => $this->faker->swiftBicNumber(),
            'currency' => 'EUR',
            'balance' => $this->faker->randomFloat(2, 1000, 50000),
            'bridge_account_id' => $this->faker->uuid(),
        ];
    }
}
