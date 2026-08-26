<?php

use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\ExpenseItemStatus;
use App\Enums\RH\ExpensePaymentMethod;
use App\Enums\RH\ExpenseReportStatus;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseItem;
use App\Models\RH\ExpenseReport;
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

    $service = new ReconciliationService;
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

    $service = new ReconciliationService;
    // t1 score = 100, t2 score = 50 (only amount matches)

    $success = $service->bulkReconcile(BankTransaction::whereIn('id', [$t1->id, $t2->id])->get(), 80);

    expect($success)->toBe(1);
    expect($t1->fresh()->status->value)->toBe('reconciled');
    expect($t2->fresh()->status->value)->toBe('pending');
});

it('suggests exact matches for corporate card expense items', function () {
    $account = BankAccount::factory()->create();

    $expenseItem = ExpenseItem::factory()->create([
        'amount_ttc' => 55.50,
        'merchant' => 'TotalEnergies',
        'date' => now()->subDays(2),
        'payment_method' => ExpensePaymentMethod::CORPORATE_CARD,
        'status' => ExpenseItemStatus::PENDING,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -55.50, // debit
        'description' => 'Achat CB TotalEnergies Paris', // Merchant matches
        'date' => now(), // within 2 days
    ]);

    $service = new ReconciliationService;
    $suggestions = $service->suggestMatches($transaction);

    // Score should be 50 (amount) + 30 (date) + 20 (merchant) = 100
    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['model']->id)->toBe($expenseItem->id)
        ->and($suggestions[0]['type'])->toBe(ExpenseItem::class)
        ->and($suggestions[0]['score'])->toBe(100);
});

it('ignores personal card expense items', function () {
    $account = BankAccount::factory()->create();

    $expenseItem = ExpenseItem::factory()->create([
        'amount_ttc' => 55.50,
        'payment_method' => ExpensePaymentMethod::PERSONAL_CARD,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -55.50,
    ]);

    $service = new ReconciliationService;
    $suggestions = $service->suggestMatches($transaction);

    $expenseItemSuggestions = array_filter($suggestions, fn ($s) => $s['type'] === ExpenseItem::class);

    expect($expenseItemSuggestions)->toBeEmpty();
});

it('suggests supplier invoices and expense reports for debit transactions', function () {
    $account = BankAccount::factory()->create();
    $supplier = ThirdParty::factory()->create(['name' => 'Fournisseur A']);

    $supplierInvoice = SupplierInvoice::factory()->create([
        'supplier_id' => $supplier->id,
        'reference' => 'F-100',
        'status' => 'draft',
        'amount_ttc' => 500.00,
    ]);

    $employee = Employee::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
    $expenseReport = ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'month' => '07',
        'year' => 2026,
        'status' => ExpenseReportStatus::VALIDATED,
        'total_amount' => 150.00,
    ]);

    // Transaction for supplier
    $txSupplier = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -500.00,
        'description' => 'Virement Fournisseur A F-100',
    ]);

    // Transaction for expense report
    $txExpense = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -150.00,
        'description' => 'Remboursement NDF Jean Dupont 2026-07',
    ]);

    $service = new ReconciliationService;

    $suggestionsSupplier = $service->suggestMatches($txSupplier);
    expect($suggestionsSupplier)->not->toBeEmpty()
        ->and($suggestionsSupplier[0]['type'])->toBe(SupplierInvoice::class)
        ->and($suggestionsSupplier[0]['model']->id)->toBe($supplierInvoice->id);

    $suggestionsExpense = $service->suggestMatches($txExpense);
    expect($suggestionsExpense)->not->toBeEmpty()
        ->and($suggestionsExpense[0]['type'])->toBe(ExpenseReport::class)
        ->and($suggestionsExpense[0]['model']->id)->toBe($expenseReport->id);
});

it('suggests payslips for debit transactions', function () {
    $account = BankAccount::factory()->create();
    $employee = Employee::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'status' => PayslipStatus::DRAFT,
        'net_payable' => 2000.00,
    ]);

    $tx = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -2000.00,
        'description' => 'Salaire Jean Dupont 2026-07',
    ]);

    $service = new ReconciliationService;
    $suggestions = $service->suggestMatches($tx);

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['type'])->toBe(Payslip::class)
        ->and($suggestions[0]['model']->id)->toBe($payslip->id);
});

it('skips expense items already reconciled', function () {
    $account = BankAccount::factory()->create();
    $expenseItem = ExpenseItem::factory()->create([
        'amount_ttc' => 50.00,
        'payment_method' => ExpensePaymentMethod::CORPORATE_CARD,
        'status' => ExpenseItemStatus::PENDING,
    ]);

    $dummyTx = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -50.00,
    ]);

    // Simulate it's already reconciled
    BankReconciliation::create([
        'bank_transaction_id' => $dummyTx->id,
        'reconcilable_type' => ExpenseItem::class,
        'reconcilable_id' => $expenseItem->id,
        'amount_applied' => 50.00,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -50.00,
    ]);

    $service = new ReconciliationService;
    $suggestions = $service->suggestMatches($transaction);

    $expenseItemSuggestions = array_filter($suggestions, fn ($s) => $s['type'] === ExpenseItem::class);
    expect($expenseItemSuggestions)->toBeEmpty();
});

it('skips non pending transactions in bulkReconcile', function () {
    $account = BankAccount::factory()->create();

    $t1 = BankTransaction::factory()->create(['bank_account_id' => $account->id, 'amount' => 100.0, 'status' => 'reconciled']);

    $service = new ReconciliationService;
    $success = $service->bulkReconcile(BankTransaction::where('id', $t1->id)->get());

    expect($success)->toBe(0);
});

it('scores expense item with diffDays between 5 and 7 correctly', function () {
    $account = BankAccount::factory()->create();

    $expenseItem = ExpenseItem::factory()->create([
        'amount_ttc' => 55.50,
        'date' => now()->subDays(6), // 6 days ago
        'payment_method' => ExpensePaymentMethod::CORPORATE_CARD,
    ]);

    $transaction = BankTransaction::factory()->create([
        'bank_account_id' => $account->id,
        'amount' => -55.50,
        'date' => now(),
    ]);

    $service = new ReconciliationService;
    $suggestions = $service->suggestMatches($transaction);

    // Score: 50 (amount) + 10 (diff <= 7) = 60
    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['score'])->toBe(60);
});
