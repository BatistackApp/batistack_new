<?php

namespace App\Observers\Flottes;

use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\VehicleContract;

class VehicleContractObserver
{
    public function saved(VehicleContract $contract): void
    {
        RecalculateVehicleTcoJob::dispatch($contract->vehicle);
    }

    public function deleted(VehicleContract $contract): void
    {
        RecalculateVehicleTcoJob::dispatch($contract->vehicle);
    }
}
