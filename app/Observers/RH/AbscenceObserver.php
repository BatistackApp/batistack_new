<?php

namespace App\Observers\RH;

use App\Models\RH\Abscence;
use Log;

class AbscenceObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Abscence $absence): void
    {
        if (! $absence->employee_id) {
            throw new \Exception('Employee required');
        }
        if (! $absence->start_date) {
            throw new \Exception('Date required');
        }
        if (empty($absence->type)) {
            throw new \Exception('Absence type required');
        }
    }

    public function created(Abscence $absence): void
    {
        Log::info('Absence created', ['id' => $absence->id, 'employee_id' => $absence->employee_id, 'type' => $absence->type, 'date' => $absence->start_date]);
    }
}
