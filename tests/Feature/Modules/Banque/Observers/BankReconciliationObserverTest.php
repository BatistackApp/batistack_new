<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Banque\BankReconciliation;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;

it('updates invoice status to paid when fully reconciled', function () {
    \Illuminate\Support\Facades\Queue::fake();
    $customer = \App\Models\Tiers\ThirdParty::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'client_id' => $customer->id,
    ]);
    
    // Total TTC needs to be mocked or retrieved
    $invoice->total_ttc = 100.00;
    $invoice->save();

    $transaction = BankTransaction::factory()->create(['amount' => 100.00]);

    // Create reconciliation
    BankReconciliation::create([
        'bank_transaction_id' => $transaction->id,
        'reconcilable_type' => CustomerInvoice::class,
        'reconcilable_id' => $invoice->id,
        'amount_applied' => 100.00,
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

it('reverts invoice status to sent when reconciliation is deleted', function () {
    \Illuminate\Support\Facades\Queue::fake();
    $customer = \App\Models\Tiers\ThirdParty::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'client_id' => $customer->id,
    ]);
    
    $invoice->total_ttc = 100.00;
    $invoice->save();

    $transaction = BankTransaction::factory()->create(['amount' => 100.00]);

    $reconciliation = BankReconciliation::create([
        'bank_transaction_id' => $transaction->id,
        'reconcilable_type' => CustomerInvoice::class,
        'reconcilable_id' => $invoice->id,
        'amount_applied' => 100.00,
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);

    $reconciliation->delete();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED);
});

it('updates invoice status to partially paid when partially reconciled', function () {
    \Illuminate\Support\Facades\Queue::fake();
    $customer = \App\Models\Tiers\ThirdParty::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'client_id' => $customer->id,
    ]);
    
    $invoice->total_ttc = 100.00;
    $invoice->save();

    $transaction = BankTransaction::factory()->create(['amount' => 30.00]);

    BankReconciliation::create([
        'bank_transaction_id' => $transaction->id,
        'reconcilable_type' => CustomerInvoice::class,
        'reconcilable_id' => $invoice->id,
        'amount_applied' => 30.00,
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
});
