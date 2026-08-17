<?php

namespace App\Jobs\Paie;

use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Paie\SalaryPaymentRun;
use App\Services\Paie\SalaryPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class InitiateSalaryPaymentRunJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public SalaryPaymentRun $run) {}

    public function handle(SalaryPaymentService $service): void
    {
        try {
            $service->initiateRun($this->run);

            Log::info("Run de paiement {$this->run->id} initié auprès de Bridge.");
        } catch (\Throwable $e) {
            Log::error("Échec de l'initiation du run de paiement {$this->run->id}: ".$e->getMessage());

            $this->run->update(['status' => SalaryPaymentStatus::FAILED]);
        }
    }
}
