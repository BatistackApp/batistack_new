<?php

use App\Models\Banque\BankTransaction;
use App\Models\Banque\CategorizationRule;
use App\Models\Banque\TransactionCategory;
use App\Services\Banque\TransactionCategorizationService;

it('categorizes a transaction based on keyword rule ignoring case', function () {
    $category = TransactionCategory::create([
        'name' => 'Carburant',
        'color' => 'red',
        'type' => 'expense',
    ]);

    CategorizationRule::create([
        'transaction_category_id' => $category->id,
        'keyword' => 'TOTAL',
    ]);

    $transaction = BankTransaction::factory()->create([
        'description' => 'Achat Station Total Paris',
    ]);

    $service = new TransactionCategorizationService();
    $result = $service->categorizeTransaction($transaction);

    expect($result)->toBeTrue()
        ->and($transaction->fresh()->transaction_category_id)->toBe($category->id);
});

it('does not categorize a transaction if no rules match', function () {
    $category = TransactionCategory::create([
        'name' => 'Carburant',
    ]);

    CategorizationRule::create([
        'transaction_category_id' => $category->id,
        'keyword' => 'ESSO',
    ]);

    $transaction = BankTransaction::factory()->create([
        'description' => 'Achat Station BP',
    ]);

    $service = new TransactionCategorizationService();
    $result = $service->categorizeTransaction($transaction);

    expect($result)->toBeFalse()
        ->and($transaction->fresh()->transaction_category_id)->toBeNull();
});

it('categorizes multiple transactions correctly', function () {
    $cat1 = TransactionCategory::create(['name' => 'Energie']);
    $cat2 = TransactionCategory::create(['name' => 'Salaire', 'type' => 'income']);

    CategorizationRule::create(['transaction_category_id' => $cat1->id, 'keyword' => 'edf']);
    CategorizationRule::create(['transaction_category_id' => $cat2->id, 'keyword' => 'virement salaire']);

    $tx1 = BankTransaction::factory()->create(['description' => 'Prélèvement EDF']);
    $tx2 = BankTransaction::factory()->create(['description' => 'Virement Salaire Juin']);
    $tx3 = BankTransaction::factory()->create(['description' => 'Achat Fnac']);

    $transactions = [$tx1, $tx2, $tx3];

    $service = new TransactionCategorizationService();
    $count = $service->categorizeMultiple($transactions);

    expect($count)->toBe(2)
        ->and($tx1->fresh()->transaction_category_id)->toBe($cat1->id)
        ->and($tx2->fresh()->transaction_category_id)->toBe($cat2->id)
        ->and($tx3->fresh()->transaction_category_id)->toBeNull();
});
