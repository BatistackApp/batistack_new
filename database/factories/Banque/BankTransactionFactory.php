<?php

namespace Database\Factories\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankTransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(TransactionType::cases());
        $amount = $this->faker->randomFloat(2, 10, 5000);
        
        return [
            'bank_account_id' => BankAccount::factory(),
            'external_id' => $this->faker->uuid(),
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence(4),
            'amount' => $type === TransactionType::CREDIT ? $amount : -$amount,
            'type' => $type,
            'status' => TransactionStatus::PENDING,
        ];
    }
}
