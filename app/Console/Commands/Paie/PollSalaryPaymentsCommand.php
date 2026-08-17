<?php

namespace App\Console\Commands\Paie;

use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Paie\SalaryPaymentRun;
use App\Services\Paie\SalaryPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollSalaryPaymentsCommand extends Command
{
    protected $signature = 'paie:poll-salary-payments';

    protected $description = 'Met à jour le statut des runs de paiement de salaires auprès de Bridge.';

    public function handle(SalaryPaymentService $service): int
    {
        $runs = SalaryPaymentRun::query()
            ->whereNotNull('bridge_payment_request_id')
            ->whereIn('status', [
                SalaryPaymentStatus::AWAITING_VALIDATION->value,
                SalaryPaymentStatus::PROCESSING->value,
            ])
            ->get();

        foreach ($runs as $run) {
            try {
                $service->pollRun($run);
                $this->line("Run {$run->id} -> {$run->fresh()->status->value}");
            } catch (\Throwable $e) {
                Log::error("Échec du polling du run de paiement {$run->id}: ".$e->getMessage());
            }
        }

        $this->info(count($runs).' run(s) de paiement vérifié(s).');

        return self::SUCCESS;
    }
}
