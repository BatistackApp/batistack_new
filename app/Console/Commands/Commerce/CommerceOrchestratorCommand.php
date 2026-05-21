<?php

namespace App\Console\Commands\Commerce;

use App\Jobs\Commerce\CheckExpiredQuotesJob;
use App\Jobs\Commerce\CheckOverdueInvoicesJob;
use Illuminate\Console\Command;

class CommerceOrchestratorCommand extends Command
{
    protected $signature = 'commerce:orchestrator
                            {--check-quotes : Vérifie les devis expirés}
                            {--check-invoices : Vérifie les factures impayées}
                            {--all : Exécute tous les contrôles (par défaut)}';

    protected $description = 'Orchestre les vérifications et automatismes du module Commerce';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║     BATISTACK - Commerce Orchestrator                  ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        $checkQuotes = $this->option('check-quotes');
        $checkInvoices = $this->option('check-invoices');
        $checkAll = $this->option('all') || (! $checkQuotes && ! $checkInvoices);

        // 1. Vérification des devis expirés
        if ($checkQuotes || $checkAll) {
            $this->line('🔍 Lancement du scan des devis expirés...');
            CheckExpiredQuotesJob::dispatch();
            $this->info('   ✓ Job envoyé en file d\'attente');
            $this->newLine();
        }

        // 2. Vérification des factures impayées
        if ($checkInvoices || $checkAll) {
            $this->line('🔍 Lancement du scan des factures impayées...');
            CheckOverdueInvoicesJob::dispatch();
            $this->info('   ✓ Job envoyé en file d\'attente');
            $this->newLine();
        }

        $this->info('✅ Tous les jobs ont été envoyés avec succès.');
        $this->info('Vérifiez la file d\'attente : php artisan queue:work');

        return self::SUCCESS;
    }
}
