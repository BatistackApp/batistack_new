<?php

namespace Tests\Feature\Modules\Commerce\Models;

use App\Enums\Commerce\PaymentMethod;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Payment Relations', function () {
    beforeEach(function () {
        Queue::fake();
        $this->thirdParty = ThirdParty::factory()->create();
        $this->payment = new Payment;
        $this->payment->forceFill([
            'third_party_id' => $this->thirdParty->id,
            'reference' => 'PAY-001',
            'type' => 'in',
            'method' => PaymentMethod::BANK_TRANSFER,
            'status' => 'completed',
            'amount' => 100,
            'payment_date' => now(),
        ])->save();
    });

    it('tests Payment relations', function () {
        expect($this->payment->thirdParty)->toBeInstanceOf(ThirdParty::class);
    });

    it('tests PaymentAllocation with CustomerInvoice', function () {
        $invoice = CustomerInvoice::factory()->create();

        $allocation = new PaymentAllocation;
        $allocation->forceFill([
            'payment_id' => $this->payment->id,
            'payable_type' => CustomerInvoice::class,
            'payable_id' => $invoice->id,
            'allocated_amount' => 50,
        ])->save();

        expect($allocation->payment)->toBeInstanceOf(Payment::class)
            ->and($allocation->payable)->toBeInstanceOf(CustomerInvoice::class);
    });

    it('tests PaymentAllocation with SupplierInvoice', function () {
        $invoice = SupplierInvoice::factory()->create(['amount_ttc' => 120]);

        $allocation = new PaymentAllocation;
        $allocation->forceFill([
            'payment_id' => $this->payment->id,
            'payable_type' => SupplierInvoice::class,
            'payable_id' => $invoice->id,
            'allocated_amount' => 50,
        ])->save();

        expect($allocation->payable)->toBeInstanceOf(SupplierInvoice::class);
    });
});
