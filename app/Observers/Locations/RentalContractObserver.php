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
        // Vérifier automatiquement la date de fin pour résiliation
        if ($contract->end_date && $contract->end_date->isBefore(today()) && $contract->status === RentalStatus::ACTIVE) {
            $contract->status = RentalStatus::TERMINATED;
        }
    }

    public function saved(RentalContract $contract): void
    {
        $this->updateSupplierScore($contract);
    }

    public function deleted(RentalContract $contract): void
    {
        $this->updateSupplierScore($contract);
    }

    private function updateSupplierScore(RentalContract $contract): void
    {
        if (!$contract->supplier_id) {
            return;
        }
        
        $supplier = $contract->supplier;

        if (!$supplier) {
            return;
        }

        $averageScore = RentalContract::where('supplier_id', $supplier->id)
            ->whereNotNull('supplier_score')
            ->where('status', RentalStatus::TERMINATED)
            ->avg('supplier_score');

        if ($averageScore !== null) {
            // Convert 1-5 scale to 0-100 scale
            $supplier->supplier_score = (int) round($averageScore * 20);
        } else {
            $supplier->supplier_score = null;
        }

        $supplier->saveQuietly();
    }
}
