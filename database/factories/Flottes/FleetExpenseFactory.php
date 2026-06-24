<?php

namespace Database\Factories\Flottes;

use App\Enums\Flottes\FleetExpenseType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FleetExpenseFactory extends Factory
{
    protected $model = FleetExpense::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(FleetExpenseType::class),
            'reference' => $this->faker->word(),
            'amount_ht' => $this->faker->randomFloat(),
            'amount_ttc' => $this->faker->randomFloat(),
            'merchant_name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'spent_at' => Carbon::now(),
            'is_suspicious' => $this->faker->boolean(),
            'suspicion_reason' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'employee_id' => Employee::factory(),
            'chantier_id' => Chantier::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
