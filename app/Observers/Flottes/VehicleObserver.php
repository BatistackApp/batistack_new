<?php

namespace App\Observers\Flottes;

use App\Models\Flottes\Vehicle;
use Illuminate\Support\Str;

class VehicleObserver
{
    public function creating(Vehicle $vehicle): void
    {
        if (empty($vehicle->reference)) {
            $latestId = Vehicle::max('id') ?? 0;
            $vehicle->reference = 'VEH-'.str_pad($latestId + 1, 3, '0', STR_PAD_LEFT);
        }

        if ($vehicle->license_plate) {
            $vehicle->license_plate = Str::upper(str_replace([' ', '-'], '', $vehicle->license_plate));
        }
    }

    public function updating(Vehicle $vehicle): void
    {
        if ($vehicle->isDirty('license_plate')) {
            $vehicle->license_plate = Str::upper(str_replace([' ', '-'], '', $vehicle->license_plate));
        }
    }
}
