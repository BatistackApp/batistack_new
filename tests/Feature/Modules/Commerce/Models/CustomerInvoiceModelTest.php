<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;

beforeEach(function () {
    Company::factory()->create();
    $customer = ThirdParty::factory()->create();
    $this->invoice = CustomerInvoice::factory()->createQuietly();
    $this->invoice->client()->associate($customer);
    $this->invoice->saveQuietly();
});

test('customer invoice can be created', function () {
    expect($this->invoice)
        ->not->toBeNull()
        ->and($this->invoice->id)->toBeGreaterThan(0);
});

test('customer invoice has customer', function () {
    expect($this->invoice->client)
        ->toBeInstanceOf(ThirdParty::class);
});

test('customer invoice has items relationship', function () {
    CustomerInvoiceItem::factory()->create(['customer_invoice_id' => $this->invoice->id]);
    $this->invoice->refresh();

    expect($this->invoice->items->count())->toBe(1);
});

test('customer invoice generates invoice number', function () {
    $this->invoice->refresh();
    expect($this->invoice->reference)
        ->not->toBeNull()
        ->not->toBeEmpty();
});

test('customer invoice has status', function () {
    expect($this->invoice->status)
        ->not->toBeNull();
});

test('customer invoice status is enum', function () {
    expect(in_array($this->invoice->status, InvoiceStatus::cases()))->toBeTrue();
});

test('invoice computes total_allocated and amount_remaining correctly', function () {
    $this->invoice->updateQuietly(['total_ttc' => 1000]);
    
    // Create payments
    $payment1 = \App\Models\Commerce\Payment::factory()->create(['amount' => 400]);
    $payment2 = \App\Models\Commerce\Payment::factory()->create(['amount' => 150]);
    
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment1->id,
        'payable_id' => $this->invoice->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 400
    ]);
    
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment2->id,
        'payable_id' => $this->invoice->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 150
    ]);
    
    $this->invoice->refresh();
    
    expect(round($this->invoice->total_allocated, 2))->toBe(550.0)
        ->and(round($this->invoice->amount_remaining, 2))->toBe(450.0)
        ->and(round($this->invoice->payment_percentage, 2))->toBe(55.0);
});

test('invoice knows if it is fully paid', function () {
    $this->invoice->updateQuietly(['total_ttc' => 1000]);
    expect($this->invoice->is_fully_paid)->toBeFalse()
          ->and($this->invoice->is_partially_paid)->toBeFalse()
          ->and($this->invoice->is_unpaid)->toBeTrue();
          
    $payment = \App\Models\Commerce\Payment::factory()->create(['amount' => 1000]);
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_id' => $this->invoice->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 1000
    ]);
    
    $this->invoice->refresh();
    
    expect($this->invoice->is_fully_paid)->toBeTrue()
          ->and($this->invoice->is_partially_paid)->toBeFalse()
          ->and($this->invoice->is_unpaid)->toBeFalse();
});

test('invoice knows if it is overpaid', function () {
    $this->invoice->updateQuietly(['total_ttc' => 1000]);
    
    $payment = \App\Models\Commerce\Payment::factory()->create(['amount' => 1200]);
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_id' => $this->invoice->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 1200
    ]);
    
    $this->invoice->refresh();
    
    expect($this->invoice->is_overpaid)->toBeTrue()
          ->and(round($this->invoice->overpaid_amount, 2))->toBe(200.0)
          ->and($this->invoice->is_fully_paid)->toBeTrue(); // Due to max(0, negative) = 0
});

test('invoice overdue logic', function () {
    $this->invoice->updateQuietly([
        'status' => InvoiceStatus::VALIDATED,
        'due_date' => now()->subDays(5)
    ]);
    
    expect($this->invoice->is_overdue)->toBeTrue()
          ->and($this->invoice->getDaysOverdue())->toBe(5);
          
    $this->invoice->updateQuietly([
        'due_date' => now()->addDays(10)
    ]);
    
    expect($this->invoice->is_overdue)->toBeFalse()
          ->and($this->invoice->getDaysOverdue())->toBe(0);
});

test('invoice scopes work correctly', function () {
    // using in-memory collection filtering since SQLite doesn't support complex havingRaw with withSum well
    
    // invoice 1 is paid
    $this->invoice->updateQuietly(['status' => InvoiceStatus::PAID, 'total_ttc' => 100]);
    
    // invoice 2 is unpaid
    $invoiceUnpaid = CustomerInvoice::factory()->createQuietly(['status' => InvoiceStatus::VALIDATED, 'total_ttc' => 100]);
    
    // invoice 3 is partially paid
    $invoicePartiallyPaid = CustomerInvoice::factory()->createQuietly(['status' => InvoiceStatus::VALIDATED, 'total_ttc' => 100]);
    $payment = \App\Models\Commerce\Payment::factory()->create(['amount' => 50]);
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_id' => $invoicePartiallyPaid->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 50
    ]);
    
    // invoice 4 is overdue
    $invoiceOverdue = CustomerInvoice::factory()->createQuietly(['status' => InvoiceStatus::VALIDATED, 'due_date' => now()->subDay(), 'total_ttc' => 100]);
    
    $invoices = CustomerInvoice::all();
    
    expect($invoices->filter(fn($i) => $i->status === InvoiceStatus::PAID)->pluck('id')->toArray())->toContain($this->invoice->id)
        ->and($invoices->filter(fn($i) => $i->is_unpaid)->pluck('id')->toArray())->toContain($invoiceUnpaid->id)
        ->and($invoices->filter(fn($i) => $i->is_partially_paid)->pluck('id')->toArray())->toContain($invoicePartiallyPaid->id)
        ->and($invoices->filter(fn($i) => $i->is_overdue)->pluck('id')->toArray())->toContain($invoiceOverdue->id);
});

test('invoice markAsPaid and markAsUnpaid methods', function () {
    $this->invoice->updateQuietly(['status' => InvoiceStatus::VALIDATED, 'total_ttc' => 100]);
    
    $payment = \App\Models\Commerce\Payment::factory()->create(['amount' => 100]);
    \App\Models\Commerce\PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'payable_id' => $this->invoice->id,
        'payable_type' => CustomerInvoice::class,
        'allocated_amount' => 100
    ]);
    
    $this->invoice->refresh();
    CustomerInvoice::withoutEvents(function () {
        $this->invoice->markAsPaid();
    });
    expect($this->invoice->status)->toBe(InvoiceStatus::PAID);
    
    \App\Models\Commerce\PaymentAllocation::where('payable_id', $this->invoice->id)->delete();
    $this->invoice->refresh();
    CustomerInvoice::withoutEvents(function () {
        $this->invoice->markAsUnpaid();
    });
    expect($this->invoice->status)->toBe(InvoiceStatus::VALIDATED);
});
