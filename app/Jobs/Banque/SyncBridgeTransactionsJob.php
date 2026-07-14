<?php

namespace App\Jobs\Banque;

use App\Models\Banque\BankAccount;
use App\Services\Banque\BridgeApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBridgeTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankAccount $account
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BridgeApiService $bridgeService): void
    {
        try {
            $imported = $bridgeService->syncTransactions($this->account);
            Log::info("Bridge Sync Job: Successfully imported {$imported} transactions for account {$this->account->id}.");
        } catch (\Exception $e) {
            Log::error("Bridge Sync Job Failed for account {$this->account->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
