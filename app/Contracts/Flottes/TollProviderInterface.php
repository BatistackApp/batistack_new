<?php

namespace App\Contracts\Flottes;

use DateTime;
use Illuminate\Support\Collection;

interface TollProviderInterface
{
    /**
     * Authenticate to the toll provider API.
     */
    public function authenticate(): bool;

    /**
     * Fetch transactions between two dates.
     * 
     * @return Collection Collection of structured data arrays representing transactions.
     */
    public function fetchTransactions(DateTime $from, DateTime $to): Collection;
}
