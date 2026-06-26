<?php

use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Tiers\ThirdParty;
use App\Enums\Commerce\InvoiceStatus;

beforeEach(function () {
    $this->invoice = CustomerInvoice::factory()->create();
});

test('customer invoice can be created', function () {
    expect($this->invoice)
        ->not->toBeNull()
        ->and($this->invoice->id)->toBeGreaterThan(0);
});

test('customer invoice has customer', function () {
    expect($this->invoice->customer)
        ->toBeInstanceOf(ThirdParty::class);
});

test('customer invoice has items relationship', function () {
    CustomerInvoiceItem::factory()->create(['customer_invoice_id' => $this->invoice->id]);
    $this->invoice->refresh();

    expect($this->invoice->items->count())->toBe(1);
});

test('customer invoice generates invoice number', function () {
    expect($this->invoice->invoice_number)
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

test('multiple invoices can exist', function () {
    CustomerInvoice::factory()->create();

    expect(CustomerInvoice::count())->toBe(2);
});

test('invoice total is numeric', function () {
    expect($this->invoice->total_ht ?? 0)->toBeNumeric();
});
