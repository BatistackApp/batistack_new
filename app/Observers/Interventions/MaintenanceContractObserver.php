<?php

namespace App\Observers\Interventions;

use App\Models\Interventions\MaintenanceContract;
use Illuminate\Support\Facades\DB;

class MaintenanceContractObserver
{
    public function creating(MaintenanceContract $contract): void
    {
        if (empty($contract->reference)) {
            $contract->reference = DB::transaction(function () {
                $year = now()->year;

                $latest = MaintenanceContract::whereYear('created_at', $year)
                    ->withTrashed()
                    ->lockForUpdate()
                    ->orderByRaw('LENGTH(reference) DESC')
                    ->orderBy('reference', 'desc')
                    ->first();

                $sequenceNumber = 1;
                if ($latest && preg_match('/^MC-'.$year.'-(\d+)$/', $latest->reference, $m)) {
                    $sequenceNumber = (int) $m[1] + 1;
                }

                return 'MC-'.$year.'-'.str_pad((string) $sequenceNumber, 4, '0', STR_PAD_LEFT);
            });
        }
    }
}
