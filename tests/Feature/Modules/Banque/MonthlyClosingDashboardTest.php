<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Banque\Widgets\HighValueAnomaliesWidget;
use App\Filament\Banque\Widgets\ManualPaidCustomerInvoicesWidget;
use App\Filament\Banque\Widgets\ManualPaidSupplierInvoicesWidget;
use App\Filament\Banque\Widgets\UncategorizedTransactionsWidget;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\Banque\TransactionCategory;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use App\Models\Commerce\SupplierInvoice;
use App\Models\User;
use App\Models\Core\Company;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays uncategorized transactions in widget', function () {
    $category = TransactionCategory::create(['name' => 'Cat1', 'slug' => 'cat1', 'type' => \App\Enums\Banque\TransactionType::CREDIT, 'color' => 'blue', 'is_active' => true]);
    $account = BankAccount::factory()->create();
    
    $categorized = BankTransaction::factory()->create(['transaction_category_id' => $category->id, 'bank_account_id' => $account->id]);
    $uncategorized = BankTransaction::factory()->create(['transaction_category_id' => null, 'description' => 'Achat divers', 'bank_account_id' => $account->id]);

    Livewire::test(UncategorizedTransactionsWidget::class)
        ->assertCanSeeTableRecords([$uncategorized])
        ->assertCanNotSeeTableRecords([$categorized]);
});

it('displays high value anomalies without reconciliations', function () {
    $account = BankAccount::factory()->create();
    $anomaly1 = BankTransaction::factory()->create(['amount' => 1500, 'bank_account_id' => $account->id]);
    $anomaly2 = BankTransaction::factory()->create(['amount' => -1000, 'bank_account_id' => $account->id]);
    
    $reconciled = BankTransaction::factory()->create(['amount' => 1200, 'bank_account_id' => $account->id]);
    BankReconciliation::factory()->create([
        'bank_transaction_id' => $reconciled->id,
        'reconcilable_type' => CustomerInvoice::class,
        'reconcilable_id' => 1,
        'amount_applied' => 1200,
    ]);
    
    $small = BankTransaction::factory()->create(['amount' => 500, 'bank_account_id' => $account->id]);

    Livewire::test(HighValueAnomaliesWidget::class)
        ->assertCanSeeTableRecords([$anomaly1, $anomaly2])
        ->assertCanNotSeeTableRecords([$reconciled, $small]);
});

it('displays customer invoices manually paid without allocations', function () {
    CustomerInvoice::flushEventListeners(); // Disable observers for test
    $invoiceAnomaly = CustomerInvoice::factory()->create(['status' => InvoiceStatus::PAID, 'total_ttc' => 100]);
    $invoiceValid = CustomerInvoice::factory()->create(['status' => InvoiceStatus::PAID, 'total_ttc' => 100]);
    
    $payment = Payment::factory()->create();
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_type' => CustomerInvoice::class,
        'payable_id' => $invoiceValid->id,
        'allocated_amount' => 100,
    ]);

    $invoiceNotPaid = CustomerInvoice::factory()->create(['status' => InvoiceStatus::VALIDATED, 'total_ttc' => 100]);

    Livewire::test(ManualPaidCustomerInvoicesWidget::class)
        ->assertCanSeeTableRecords([$invoiceAnomaly])
        ->assertCanNotSeeTableRecords([$invoiceValid, $invoiceNotPaid]);
});

it('displays supplier invoices manually paid without allocations', function () {
    SupplierInvoice::flushEventListeners(); // Disable observers for test
    $invoiceAnomaly = SupplierInvoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount_ttc' => 100, 'amount_ht' => 80]);
    $invoiceValid = SupplierInvoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount_ttc' => 100, 'amount_ht' => 80]);
    
    $payment = Payment::factory()->create();
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_type' => SupplierInvoice::class,
        'payable_id' => $invoiceValid->id,
        'allocated_amount' => 100,
    ]);

    $invoiceNotPaid = SupplierInvoice::factory()->create(['status' => InvoiceStatus::BON_A_PAYER, 'amount_ttc' => 100, 'amount_ht' => 80]);

    Livewire::test(ManualPaidSupplierInvoicesWidget::class)
        ->assertCanSeeTableRecords([$invoiceAnomaly])
        ->assertCanNotSeeTableRecords([$invoiceValid, $invoiceNotPaid]);
});
