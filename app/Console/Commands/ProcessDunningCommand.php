<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Commerce\DuePaymentService;

class ProcessDunningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:process-dunning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les factures échues et envoie les relances automatiques';

    /**
     * Execute the console command.
     */
    public function handle(DuePaymentService $service)
    {
        $this->info('Démarrage du processus de relance des impayés...');
        
        $service->processOverdueInvoices();
        
        $this->info('Processus terminé.');
    }
}
