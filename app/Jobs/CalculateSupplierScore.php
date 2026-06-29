<?php

namespace App\Jobs;

use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\SupplierScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateSupplierScore implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ThirdParty $supplier
    ) {}

    public function handle(SupplierScoringService $service): void
    {
        $service->calculateScore($this->supplier);
    }
}
