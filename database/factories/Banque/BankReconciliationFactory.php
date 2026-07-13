<?php

namespace Database\Factories\Banque;

use App\Models\Banque\BankTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankReconciliationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bank_transaction_id' => BankTransaction::factory(),
            'reconcilable_type' => User::class,
            'reconcilable_id' => User::factory(),
            'amount_applied' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
