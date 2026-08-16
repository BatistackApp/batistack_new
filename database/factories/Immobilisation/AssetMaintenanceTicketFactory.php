<?php

namespace Database\Factories\Immobilisation;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\TicketSeverity;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMaintenanceTicket>
 */
class AssetMaintenanceTicketFactory extends Factory
{
    protected $model = AssetMaintenanceTicket::class;

    public function definition(): array
    {
        return [
            'asset_type' => FixedAsset::class,
            'asset_id' => FixedAsset::factory(),
            'reported_by_id' => Employee::factory(),
            'description' => $this->faker->sentence(),
            'severity' => TicketSeverity::MEDIUM,
            'status' => AssetMaintenanceTicketStatus::OPEN,
        ];
    }

    public function forFixedAsset(?FixedAsset $fixedAsset = null): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => FixedAsset::class,
            'asset_id' => $fixedAsset ?? FixedAsset::factory(),
        ]);
    }

    public function forEquipement(?Equipement $equipement = null): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => Equipement::class,
            'asset_id' => $equipement ?? Equipement::factory(),
        ]);
    }
}
