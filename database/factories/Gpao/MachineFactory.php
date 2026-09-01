<?php

namespace Database\Factories\Gpao;

use App\Enums\Gpao\MachineStatus;
use App\Models\Gpao\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' '.ucfirst(fake()->randomElement(['CNC', 'Presse', 'Tour', 'Fraiseuse', 'Scie', 'Robot'])),
            'reference' => 'MAC-'.strtoupper(fake()->bothify('??-####')),
            'status' => MachineStatus::OPERATIONAL,
            'usage_hours' => fake()->randomFloat(2, 0, 2000),
            'maintenance_interval_hours' => 500.00,
        ];
    }

    public function operational(): static
    {
        return $this->state(fn () => ['status' => MachineStatus::OPERATIONAL]);
    }

    public function inMaintenance(): static
    {
        return $this->state(fn () => ['status' => MachineStatus::MAINTENANCE]);
    }

    public function outOfService(): static
    {
        return $this->state(fn () => ['status' => MachineStatus::OUT_OF_SERVICE]);
    }
}
