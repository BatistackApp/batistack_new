<?php

use App\Enums\Laser\InvoiceStatus;
use App\Models\Accounting\EcritureComptable;
use App\Models\Commerce\Payment;
use App\Models\Core\Company;
use App\Models\Laser\LaserInvoice;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\PaymentRecordingService;
use App\Services\Commerce\PaymentService;
use App\Services\Laser\LaserInvoiceAccountingService;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Company::factory()->create();
    Bus::fake();
    $this->client = ThirdParty::factory()->state(['type' => 'client'])->create();
});

function laserInvoiceForPayment(ThirdParty $client, float $total = 120): LaserInvoice
{
    $invoice = LaserInvoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => $total / 1.2,
        'total_tva' => $total / 6,
        'total_ttc' => $total,
    ]);

    app(LaserInvoiceAccountingService::class)->syncInvoice($invoice);

    return $invoice;
}

function paymentForLaserInvoice(ThirdParty $client, float $amount): Payment
{
    return Payment::factory()->create([
        'third_party_id' => $client->id,
        'type' => 'in',
        'amount' => $amount,
        'payment_date' => now(),
    ]);
}

it('creates bank entries and letters a fully paid laser invoice', function () {
    $invoice = laserInvoiceForPayment($this->client);
    $payment = paymentForLaserInvoice($this->client, 120);

    app(PaymentService::class)->allocatePayment($payment, $invoice, 120);

    $entries = EcritureComptable::where('reconcilable_type', 'App\Models\Commerce\PaymentAllocation')->get();

    expect($entries)->toHaveCount(2)
        ->and((float) $entries->sum('debit'))->toBe(120.0)
        ->and((float) $entries->sum('credit'))->toBe(120.0)
        ->and(EcritureComptable::where('reconcilable_id', $invoice->id)->value('lettrage'))->toBe('LET-'.$invoice->reference);
});

it('does not letter a partially paid laser invoice', function () {
    $invoice = laserInvoiceForPayment($this->client);
    $payment = paymentForLaserInvoice($this->client, 50);

    app(PaymentService::class)->allocatePayment($payment, $invoice, 50);

    expect(EcritureComptable::where('reconcilable_type', 'App\Models\Commerce\PaymentAllocation')->count())->toBe(2)
        ->and(EcritureComptable::where('reconcilable_id', $invoice->id)->value('lettrage'))->toBeNull();
});

it('removes payment entries when a laser payment is cancelled', function () {
    $invoice = laserInvoiceForPayment($this->client);
    $payment = paymentForLaserInvoice($this->client, 120);
    $allocation = app(PaymentService::class)->allocatePayment($payment, $invoice, 120);

    app(PaymentRecordingService::class)->cancelPayment($payment, 'Test');

    expect(EcritureComptable::where('reconcilable_type', $allocation->getMorphClass())
        ->where('reconcilable_id', $allocation->id)->count())->toBe(0);
});
