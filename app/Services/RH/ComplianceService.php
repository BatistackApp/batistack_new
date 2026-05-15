<?php

namespace App\Services\RH;

use App\Models\RH\Qualification;
use Illuminate\Database\Eloquent\Collection;

class ComplianceService
{
    /**
     * Identifie les habilitations arrivant à expiration sous 30 jours.
     */
    public function getExpiringQualifications(int $days = 30): Collection
    {
        return Qualification::where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->with('employee')
            ->get();
    }
}
