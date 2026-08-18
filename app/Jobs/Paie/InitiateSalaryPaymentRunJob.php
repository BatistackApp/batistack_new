<?php

namespace App\Jobs\Paie;

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
            $created = $service->initiateRun($this->run);

            if ($created) {
                Log::info("Run de paiement {$this->run->id} initié auprès de Bridge.");
            }
        } catch (\Throwable $e) {
            // On ne marque pas le run en échec : une erreur de transport peut survenir
            // après la création de la demande côté Bridge. Le run reste réconciliable
            // via le polling et l'action « Relancer l'initiation ».
            Log::error("Échec de l'initiation du run de paiement {$this->run->id}: ".$e->getMessage());
        }
    }
}
