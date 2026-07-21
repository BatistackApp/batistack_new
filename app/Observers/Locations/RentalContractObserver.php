<?php

namespace App\Observers\Locations;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;

class RentalContractObserver
{
    /**
     * Handle the RentalContract "saving" event.
     */
    public function saving(RentalContract $contract): void
    {
        // VÃ©rifier automatiquement la date de fin pour rÃ©siliation
        if ($contract->end_date && $contract->end_date->isBefore(today()) && $contract->status === RentalStatus::ACTIVE) {
            $contract->status = RentalStatus::TERMINATED;
        }
    }
}
