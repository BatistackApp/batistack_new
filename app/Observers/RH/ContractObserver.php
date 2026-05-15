<?php

namespace App\Observers\RH;

use App\Enums\RH\ContractType;
use App\Models\RH\Contract;

class ContractObserver
{
    public function creating(Contract $contract): void
    {
        match ($contract->type) {
            ContractType::CDI => $contract->trial_end_date = $contract->start_date->addDays(60),
            ContractType::CDD => $contract->trial_end_date = $contract->start_date->addDays(15),
            ContractType::INTERIM => $contract->trial_end_date = $contract->start_date,
            ContractType::APPRENTICE => $contract->trial_end_date = $contract->start_date->addDays(30),
        };
    }
}
