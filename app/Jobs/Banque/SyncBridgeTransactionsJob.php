<?php

namespace App\Jobs\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\User;
use App\Services\Banque\BridgeApiService;
use App\Services\Banque\TransactionCategorizationService;
use Filament\Notifications\Notification;
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
        public BankAccount $account,
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BridgeApiService $bridgeService): void
    {
        try {
            $imported = $bridgeService->syncTransactions($this->account);
            Log::info("Bridge Sync Job: Successfully imported {$imported} transactions for account {$this->account->id}.");

            // Catégoriser les transactions non catégorisées (on pourrait restreindre à celles importées aujourd'hui pour optimiser)
            $uncategorized = BankTransaction::where('bank_account_id', $this->account->id)
                ->whereNull('transaction_category_id')
                ->get();
            $categorizationService = new TransactionCategorizationService;
            $categorized = $categorizationService->categorizeMultiple($uncategorized);

            if ($categorized > 0) {
                Log::info("Categorization: {$categorized} transactions automatically categorized for account {$this->account->id}.");
            }

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    Notification::make()
                        ->title('Synchronisation terminée')
                        ->body("L'importation des transactions pour le compte '{$this->account->name}' est terminée ({$imported} transactions).")
                        ->success()
                        ->sendToDatabase($user);
                }
            }
        } catch (\Exception $e) {
            Log::error("Bridge Sync Job Failed for account {$this->account->id}: ".$e->getMessage());

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    Notification::make()
                        ->title('Erreur de synchronisation')
                        ->body("Une erreur est survenue lors de la synchronisation du compte '{$this->account->name}'.")
                        ->danger()
                        ->sendToDatabase($user);
                }
            }
            throw $e;
        }
    }
}
