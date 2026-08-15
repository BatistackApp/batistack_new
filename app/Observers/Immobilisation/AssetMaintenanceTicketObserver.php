<?php

namespace App\Observers\Immobilisation;

use App\Models\Immobilisation\AssetMaintenanceTicket;
use Illuminate\Support\Facades\DB;

class AssetMaintenanceTicketObserver
{
    public function creating(AssetMaintenanceTicket $ticket): void
    {
        if (empty($ticket->reference)) {
            $ticket->reference = DB::transaction(function () {
                $year = now()->year;

                $latest = AssetMaintenanceTicket::whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->orderByRaw('LENGTH(reference) DESC')
                    ->orderBy('reference', 'desc')
                    ->first();

                $sequenceNumber = 1;
                if ($latest && preg_match('/^TK-'.$year.'-(\d+)$/', $latest->reference, $m)) {
                    $sequenceNumber = (int) $m[1] + 1;
                }

                return 'TK-'.$year.'-'.str_pad((string) $sequenceNumber, 4, '0', STR_PAD_LEFT);
            });
        }
    }
}
