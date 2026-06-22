<?php

namespace App\Observers\RH;

use App\Models\RH\Contract;
use Log;

class ContractObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Contract $contract): void
    {
        if (! $contract->employee_id) {
            throw new \Exception('Employee required');
        }
        if (! $contract->start_date) {
            throw new \Exception('Start date required');
        }
        if ($contract->hourly_rate < 0) {
            throw new \Exception('Amounts must be positive');
        }
    }

    public function created(Contract $contract): void
    {
        Log::info('Contract created', ['id' => $contract->id, 'type' => $contract->type->getLabel(), 'employee_id' => $contract->employee_id]);
    }

    /**
     * @throws \Exception
     */
    public function deleting(Contract $contract): void
    {
        if ($contract->isActive()) {
            throw new \Exception('Cannot delete active contract');
        }
    }
}
