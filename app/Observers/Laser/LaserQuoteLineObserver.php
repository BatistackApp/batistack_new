<?php

namespace App\Observers\Laser;

use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserQuoteLine;

class LaserQuoteLineObserver
{
    public function created(LaserQuoteLine $line): void
    {
        $this->refreshQuote($line);
    }

    public function updated(LaserQuoteLine $line): void
    {
        $this->refreshQuote($line);
    }

    public function deleted(LaserQuoteLine $line): void
    {
        $this->refreshQuote($line);
    }

    private function refreshQuote(LaserQuoteLine $line): void
    {
        $quote = $line->quote;

        $quote->recalculateTotals();

        GenerateLaserDocumentJob::dispatch('laser_quote', $quote);
    }
}
