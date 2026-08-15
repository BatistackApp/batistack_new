<?php

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\Chantiers\Chantier;

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

it('filters by incomes and expenses', function () {
    BankTransaction::factory()->create(['type' => TransactionType::CREDIT]);
    BankTransaction::factory()->create(['type' => TransactionType::DEBIT]);

    expect(BankTransaction::incomes()->count())->toBe(1)
        ->and(BankTransaction::expenses()->count())->toBe(1);
});

it('filters by this month and last six months', function () {
    BankTransaction::factory()->create(['date' => now()]);
    BankTransaction::factory()->create(['date' => now()->subMonths(7)]);

    expect(BankTransaction::thisMonth()->count())->toBe(1)
        ->and(BankTransaction::lastSixMonths()->count())->toBe(1);
});

it('belongs to a chantier', function () {
    $chantier = Chantier::factory()->create();
    $transaction = BankTransaction::factory()->create(['chantier_id' => $chantier->id]);

    expect($transaction->chantier->id)->toBe($chantier->id)
        ->and($chantier->bankTransactions->pluck('id'))->toContain($transaction->id);
});
