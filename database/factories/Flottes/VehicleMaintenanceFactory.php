<?php

namespace Database\Factories\Flottes;

use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleMaintenanceFactory extends Factory
{
    protected $model = VehicleMaintenance::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'description' => $this->faker->text(),
            'cost_ht' => $this->faker->randomFloat(),
            'odometer_at_maintenance' => $this->faker->randomFloat(),
            'performed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'supplier_id' => ThirdParty::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
