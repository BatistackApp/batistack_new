<?php

namespace App\Services\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BankinApiService
{
    /**
     * Simulate a synchronization of transactions from Bankin/Bridge.
     */
    public function syncTransactions(BankAccount $account): int
    {
        // For simulation purposes, generate some random transactions
        // In reality: Http::withToken(...)->get('https://api.bridgeapi.io/v2/accounts/' . $account->bankin_item_id . '/transactions');
        
        $count = rand(1, 5);
        $imported = 0;

        for ($i = 0; $i < $count; $i++) {
            $isCredit = rand(0, 1) === 1;
            $amount = rand(1000, 500000) / 100;
            
            BankTransaction::firstOrCreate([
                'bank_account_id' => $account->id,
                'external_id' => 'sim_bankin_' . Str::random(10),
            ], [
                'date' => Carbon::now()->subDays(rand(0, 10))->format('Y-m-d'),
                'description' => 'Virement ' . ($isCredit ? 'Client' : 'Fournisseur') . ' ' . Str::random(5),
                'amount' => $isCredit ? $amount : -$amount,
                'type' => $isCredit ? TransactionType::CREDIT : TransactionType::DEBIT,
                'status' => TransactionStatus::PENDING,
            ]);
            $imported++;
        }

        return $imported;
    }
}
