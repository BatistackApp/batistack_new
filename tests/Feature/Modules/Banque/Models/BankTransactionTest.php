<?php

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;

it('belongs to a bank account', function () {
    $account = BankAccount::factory()->create();
    $transaction = BankTransaction::factory()->create(['bank_account_id' => $account->id]);

    expect($transaction->bankAccount->id)->toBe($account->id);
});

it('casts enums correctly', function () {
    $transaction = BankTransaction::factory()->create([
        'type' => TransactionType::CREDIT,
        'status' => TransactionStatus::PENDING,
    ]);

    expect($transaction->type)->toBe(TransactionType::CREDIT)
        ->and($transaction->status)->toBe(TransactionStatus::PENDING);
});
