<?php

namespace App\Services\Banque;

use App\Models\Banque\BankTransaction;
use App\Models\Banque\CategorizationRule;
use Illuminate\Database\Eloquent\Collection;

class TransactionCategorizationService
{
    /**
     * @var Collection|null
     */
    protected $rules = null;

    /**
     * Load rules if not already loaded.
     */
    protected function loadRules(): void
    {
        if ($this->rules === null) {
            $this->rules = CategorizationRule::all();
        }
    }

    /**
     * Apply categorization rules to a single transaction.
     */
    public function categorizeTransaction(BankTransaction $transaction): bool
    {
        $this->loadRules();
        $description = strtolower($transaction->description);

        foreach ($this->rules as $rule) {
            $keyword = strtolower($rule->keyword);
            if (str_contains($description, $keyword)) {
                $transaction->transaction_category_id = $rule->transaction_category_id;
                $transaction->save();

                return true; // Stop at first match
            }
        }

        return false;
    }

    /**
     * Apply rules to a collection of transactions.
     */
    public function categorizeMultiple(iterable $transactions): int
    {
        $count = 0;
        foreach ($transactions as $tx) {
            if ($this->categorizeTransaction($tx)) {
                $count++;
            }
        }

        return $count;
    }
}
