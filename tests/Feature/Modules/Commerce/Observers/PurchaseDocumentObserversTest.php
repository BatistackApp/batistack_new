<?php

namespace Tests\Feature\Modules\Commerce\Observers;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Jobs\CalculateSupplierScore;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierInvoice;
use App\Services\Commerce\SupplierInvoiceAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;

uses(RefreshDatabase::class);

describe('Purchase Document Observers', function () {
    it('dispatches supplier score calculation on receipt note created', function () {
        Bus::fake();

        $note = ReceiptNote::factory()->create(['status' => DeliveryStatus::PREPARATION]);

        Bus::assertDispatched(CalculateSupplierScore::class);
    });

    it('audits supplier invoice on audit status', function () {
        $serviceMock = Mockery::mock(SupplierInvoiceAuditService::class);
        $serviceMock->shouldReceive('auditInvoice')->once()->andReturn(['is_valid' => true, 'disputes' => []]);
        app()->instance(SupplierInvoiceAuditService::class, $serviceMock);

        $invoice = SupplierInvoice::factory()->create(['amount_ttc' => 120, 'status' => InvoiceStatus::DRAFT]);
        
        $invoice->update(['status' => InvoiceStatus::AUDIT]);
    });
});
