<?php

namespace Database\Factories\RH;

use App\Enums\RH\EquipementType;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(EquipementType::class),
            'label' => $this->faker->word(),
            'brand' => $this->faker->word(),
            'model_name' => $this->faker->name(),
            'serial_number' => $this->faker->word(),
            'assigned_at' => Carbon::now(),
            'expires_at' => Carbon::now(),
            'last_check_at' => Carbon::now(),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'employee_id' => Employee::factory(),
        ];
    }
}
