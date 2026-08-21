<?php

namespace App\Console\Commands\Locations;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalBillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringRentalsCommand extends Command
{
    protected $signature = 'locations:process-billing';

    protected $description = 'Traite les contrats de location arrivant à échéance et génère les factures récurrentes.';

    public function handle(RentalBillingService $billingService): int
    {
        $this->info('Démarrage du traitement de la facturation récurrente des locations...');

        $today = Carbon::today();

        $contracts = RentalContract::query()
            ->where('status', RentalStatus::ACTIVE)
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $today)
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('Aucun contrat à facturer aujourd\'hui.');

            return 0;
        }

        $count = 0;

        foreach ($contracts as $contract) {
            try {
                $invoice = $billingService->generateDraftInvoice($contract);

                $contract->update([
                    'next_billing_date' => $contract->calculateNextBillingDate(),
                ]);

                $this->line("Facture générée pour le contrat {$contract->reference}: {$invoice->uuid} (prochaine échéance: {$contract->next_billing_date->format('d/m/Y')})");
                $count++;
            } catch (\Exception $e) {
                Log::error("Erreur de facturation pour le contrat {$contract->reference}: ".$e->getMessage());
                $this->error("Erreur sur {$contract->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Traitement terminé. {$count} facture(s) générée(s).");

        return $count;
    }
}
