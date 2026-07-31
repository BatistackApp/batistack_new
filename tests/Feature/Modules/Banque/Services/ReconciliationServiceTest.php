<?php

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use App\Services\Banque\ReconciliationService;

it('suggests exact matches with high score', function () {
    $account = BankAccount::factory()->create();
    $customer = ThirdParty::factory()->create(['name' => 'Dupont']);
    
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $customer->id,
        'reference' => 'FAC-2026-001',
        'status' => 'draft',
    ]);
    // Force amount to 1500.00
    $invoice->total_ttc = 1500.00;
    $invoice->save();
    // Assuming dynamic or we just mock amount
    // Wait, CustomerInvoice factory creates items which calculate total.
    // Instead of fighting the factory, we read the generated total.
    $amount = $invoice->total_ttc ?? $invoice->amount_ttc ?? 1500.00;

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => $amount, // Exact amount match
        'description' => 'Virement de Dupont ref FAC-2026-001', // Name and Ref match
    ]);

    $service = new ReconciliationService();
    $suggestions = $service->suggestMatches($transaction);

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['model']->id)->toBe($invoice->id)
        ->and($suggestions[0]['score'])->toBe(100); // 50 (amount) + 40 (ref) + 10 (name)
});

it('bulkReconcile processes transactions above threshold', function () {
    $account = BankAccount::factory()->create();
    $customer = ThirdParty::factory()->create(['name' => 'Martin']);
    
    $invoice1 = CustomerInvoice::factory()->create(['client_id' => $customer->id, 'reference' => 'FAC-001', 'status' => 'draft']);
    $invoice1->total_ttc = 100.0;
    $invoice1->save();
    
    $invoice2 = CustomerInvoice::factory()->create(['client_id' => $customer->id, 'reference' => 'FAC-002', 'status' => 'draft']);
    $invoice2->total_ttc = 200.0;
    $invoice2->save();

    $t1 = BankTransaction::factory()->create(['bank_account_id' => $account->id, 'amount' => 100.0, 'description' => 'Virement Martin FAC-001', 'status' => 'pending']);
    $t2 = BankTransaction::factory()->create(['bank_account_id' => $account->id, 'amount' => 200.0, 'description' => 'Virement divers', 'status' => 'pending']);

    $service = new ReconciliationService();
    // t1 score = 100, t2 score = 50 (only amount matches)
    
    $success = $service->bulkReconcile(BankTransaction::whereIn('id', [$t1->id, $t2->id])->get(), 80);
    
    expect($success)->toBe(1);
    expect($t1->fresh()->status->value)->toBe('reconciled');
    expect($t2->fresh()->status->value)->toBe('pending');
});

it('suggests exact matches for corporate card expense items', function () {
    $account = BankAccount::factory()->create();
    
    $expenseItem = \App\Models\RH\ExpenseItem::factory()->create([
        'amount_ttc' => 55.50,
        'merchant' => 'TotalEnergies',
        'date' => now()->subDays(2),
        'payment_method' => \App\Enums\RH\ExpensePaymentMethod::CORPORATE_CARD,
        'status' => \App\Enums\RH\ExpenseItemStatus::PENDING,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -55.50, // debit
        'description' => 'Achat CB TotalEnergies Paris', // Merchant matches
        'date' => now(), // within 2 days
    ]);

    $service = new ReconciliationService();
    $suggestions = $service->suggestMatches($transaction);

    // Score should be 50 (amount) + 30 (date) + 20 (merchant) = 100
    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['model']->id)->toBe($expenseItem->id)
        ->and($suggestions[0]['type'])->toBe(\App\Models\RH\ExpenseItem::class)
        ->and($suggestions[0]['score'])->toBe(100);
});

it('ignores personal card expense items', function () {
    $account = BankAccount::factory()->create();
    
    $expenseItem = \App\Models\RH\ExpenseItem::factory()->create([
        'amount_ttc' => 55.50,
        'payment_method' => \App\Enums\RH\ExpensePaymentMethod::PERSONAL_CARD,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -55.50,
    ]);

    $service = new ReconciliationService();
    $suggestions = $service->suggestMatches($transaction);

    $expenseItemSuggestions = array_filter($suggestions, fn($s) => $s['type'] === \App\Models\RH\ExpenseItem::class);
    
    expect($expenseItemSuggestions)->toBeEmpty();
});
