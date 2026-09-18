<?php

use App\Enums\Laser\InvoiceStatus;
use App\Models\Accounting\AccountingSync;
use App\Models\Accounting\EcritureComptable;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Services\Laser\LaserInvoiceAccountingService;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();
});

it('creates balanced accounting entries for a validated laser invoice', function () {
    $invoice = LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 100,
        'total_tva' => 20,
        'total_ttc' => 120,
    ]);

    $sync = app(LaserInvoiceAccountingService::class)->syncInvoice($invoice);
    $entries = EcritureComptable::where('reconcilable_id', $invoice->id)
        ->where('reconcilable_type', $invoice->getMorphClass())
        ->get();

    expect($sync->status)->toBe('synced')
        ->and($entries)->toHaveCount(3)
        ->and((float) $entries->sum('debit'))->toBe(120.0)
        ->and((float) $entries->sum('credit'))->toBe(120.0)
        ->and($entries->pluck('compte_numero')->all())->toContain('704000', '445711', '411100');
});

it('does not duplicate accounting entries when a laser invoice is synchronized twice', function () {
    $invoice = LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 100,
        'total_tva' => 20,
        'total_ttc' => 120,
    ]);
    $service = app(LaserInvoiceAccountingService::class);

    $service->syncInvoice($invoice);
    $service->syncInvoice($invoice);

    expect(EcritureComptable::where('reconcilable_id', $invoice->id)->count())->toBe(3)
        ->and(AccountingSync::where('syncable_id', $invoice->id)->count())->toBe(1);
});

it('creates reversed balanced entries for a validated laser credit note', function () {
    $creditNote = LaserCreditNote::factory()->create([
        'status' => 'validated',
        'total_ht' => 100,
        'total_tva' => 20,
        'total_ttc' => 120,
    ]);

    app(LaserInvoiceAccountingService::class)->syncCreditNote($creditNote);

    $entries = EcritureComptable::where('reconcilable_id', $creditNote->id)->get();

    expect($entries)->toHaveCount(3)
        ->and((float) $entries->sum('debit'))->toBe(120.0)
        ->and((float) $entries->sum('credit'))->toBe(120.0)
        ->and((float) $entries->where('compte_numero', '411100')->sum('credit'))->toBe(120.0)
        ->and((float) $entries->where('compte_numero', '704000')->sum('debit'))->toBe(100.0)
        ->and((float) $entries->where('compte_numero', '445711')->sum('debit'))->toBe(20.0);
});
