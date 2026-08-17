<?php

namespace App\Console\Commands\Locations;

use App\Services\Locations\InternalRentalBillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateInternalRentalInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:bill-internal-rentals {--reference= : Date de référence (Y-m-d) pour la génération}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les factures internes (refacturation) des immobilisations affectées à un chantier.';

    /**
     * Execute the console command.
     */
    public function handle(InternalRentalBillingService $billingService): int
    {
        $reference = $this->option('reference')
            ? Carbon::parse($this->option('reference'))
            : null;

        $this->info('Démarrage de la facturation interne des immobilisations...');

        $invoices = $billingService->generateDueInvoices($reference);

        foreach ($invoices as $invoice) {
            $this->line("Facture interne générée {$invoice->billing_key} : {$invoice->amount_ht} €");
        }

        $this->info('Traitement terminé. '.count($invoices).' factures internes générées.');

        if ($invoices === []) {
            Log::info('Aucune facture interne générée.');
        }

        return self::SUCCESS;
    }
}
