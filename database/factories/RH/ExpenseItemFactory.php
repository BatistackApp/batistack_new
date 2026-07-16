<?php

namespace Database\Factories\RH;

use App\Models\RH\ExpenseItem;
use App\Enums\RH\ExpenseItemStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RH\ExpenseItem>
 */
class ExpenseItemFactory extends Factory
{
    protected $model = ExpenseItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_report_id' => \App\Models\RH\ExpenseReport::factory(),
            'chantier_id' => null,
            'category' => $this->faker->randomElement(['Carburant', 'Péage', 'Parking', 'Repas', 'Hébergement', 'Autre']),
            'date' => $this->faker->date(),
            'amount_ttc' => $this->faker->randomFloat(2, 10, 500),
            'amount_ht' => $this->faker->randomFloat(2, 8, 400),
            'vat_amount' => $this->faker->randomFloat(2, 2, 100),
            'merchant' => $this->faker->company,
            'status' => ExpenseItemStatus::PENDING,
            'rejection_reason' => null,
        ];
    }
}
