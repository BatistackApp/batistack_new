<?php

namespace App\Console\Commands\Banque;

use App\Jobs\Banque\SyncBridgeTransactionsJob;
use App\Models\Banque\BankAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllBridgeAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banque:sync-bridge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to sync transactions for all connected Bridge API accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching connected bank accounts...');

        $accounts = BankAccount::whereNotNull('bridge_account_id')->get();

        if ($accounts->isEmpty()) {
            $this->info('No connected Bridge accounts found.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($accounts as $account) {
            SyncBridgeTransactionsJob::dispatch($account);
            $count++;
        }

        $message = "Dispatched sync jobs for {$count} bank accounts.";
        $this->info($message);
        Log::info("Command banque:sync-bridge: {$message}");

        return self::SUCCESS;
    }
}
