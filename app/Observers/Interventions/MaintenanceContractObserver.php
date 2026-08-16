<?php

namespace App\Observers\Interventions;

use App\Models\Interventions\MaintenanceContract;
use App\Support\ReferenceGenerator;

class MaintenanceContractObserver
{
    public function creating(MaintenanceContract $contract): void
    {
        if (empty($contract->reference)) {
            $contract->reference = ReferenceGenerator::next('MC');
        }
    }
}
