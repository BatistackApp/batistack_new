<?php

namespace Database\Factories\Gpao;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Models\Gpao\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineMaintenanceTicketFactory extends Factory
{
    protected $model = \App\Models\Gpao\MachineMaintenanceTicket::class;

    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'type' => MachineMaintenanceTicketType::PREVENTIVE,
            'status' => MachineMaintenanceTicketStatus::OPEN,
            'description' => fake()->sentence(),
            'reported_by_id' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => MachineMaintenanceTicketStatus::OPEN]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => MachineMaintenanceTicketStatus::IN_PROGRESS]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => MachineMaintenanceTicketStatus::RESOLVED,
            'resolved_at' => now(),
            'cost_ht' => fake()->randomFloat(2, 50, 5000),
            'provider_name' => fake()->company(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['status' => MachineMaintenanceTicketStatus::CANCELED]);
    }

    public function preventive(): static
    {
        return $this->state(fn () => ['type' => MachineMaintenanceTicketType::PREVENTIVE]);
    }

    public function curative(): static
    {
        return $this->state(fn () => ['type' => MachineMaintenanceTicketType::CURATIVE]);
    }

    public function corrective(): static
    {
        return $this->state(fn () => ['type' => MachineMaintenanceTicketType::CORRECTIVE]);
    }
}
