<?php

use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\User;

it('belongs to a bank transaction', function () {
    $transaction = BankTransaction::factory()->create();
    $reconciliation = BankReconciliation::factory()->create(['bank_transaction_id' => $transaction->id]);

    expect($reconciliation->bankTransaction->id)->toBe($transaction->id);
});

it('morphs to a reconcilable model', function () {
    $user = User::factory()->create();
    $reconciliation = BankReconciliation::factory()->create([
        'reconcilable_type' => User::class,
        'reconcilable_id' => $user->id,
    ]);

    expect($reconciliation->reconcilable)->toBeInstanceOf(User::class)
        ->and($reconciliation->reconcilable->id)->toBe($user->id);
});
