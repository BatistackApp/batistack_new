<?php

namespace App\Console\Commands\Locations;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringRentalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:process-billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traite les contrats de location et gÃ©nÃ¨re les factures rÃ©currentes si nÃ©cessaire.';

    /**
     * Execute the console command.
     */
    public function handle(RentalBillingService $billingService)
    {
        $this->info('DÃ©marrage du traitement de la facturation rÃ©currente des locations...');

        // Dans un cas rÃ©el, on vÃ©rifie si la date d'anniversaire est atteinte 
        // par rapport Ã  la pÃ©riode de facturation. Ici pour simplifier, on sÃ©lectionne les actifs
        // On pourrait ajouter un champ `last_billed_at` ou `next_billing_date` sur RentalContract
        
        $contracts = RentalContract::query()
            ->where('status', RentalStatus::ACTIVE)
            ->get();

        $count = 0;
        foreach ($contracts as $contract) {
            // Simplification: on gÃ©nÃ¨re si c'est le jour J de la facturation
            // Pour le TP, on gÃ©nÃ¨re toujours (Ã  affiner dans une V2)
            // if (today()->isSameDay($contract->next_billing_date)) { ... }
            
            try {
                $invoice = $billingService->generateDraftInvoice($contract);
                $this->line("Facture gÃ©nÃ©rÃ©e pour le contrat {$contract->reference}: {$invoice->uuid}");
                $count++;
            } catch (\Exception $e) {
                Log::error("Erreur de facturation pour le contrat {$contract->reference}: " . $e->getMessage());
                $this->error("Erreur sur {$contract->reference}");
            }
        }

        $this->info("Traitement terminÃ©. {$count} factures gÃ©nÃ©rÃ©es.");
    }
}
