<?php

namespace App\Observers\Immobilisation;

use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Support\ReferenceGenerator;

class AssetMaintenanceTicketObserver
{
    public function creating(AssetMaintenanceTicket $ticket): void
    {
        if (empty($ticket->reference)) {
            $ticket->reference = ReferenceGenerator::next('TK');
        }
    }
}
