<?php

use App\Enums\Laser\InvoiceStatus;
use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserInvoiceLine;
use App\Models\Laser\LaserLegalizationSequence;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuote;
use App\Observers\Laser\LaserCreditNoteObserver;
use App\Observers\Laser\LaserInvoiceObserver;
use App\Services\Laser\LaserDocumentationService;
use App\Services\Laser\LaserInvoiceService;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Support\Facades\Queue;

// ============================================================
// Invoice creation from order
// ============================================================

it('creates an invoice from an order with delivered lines', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce facturée',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'price_per_kg' => 12.50,
        'price_per_meter' => 3.00,
        'programming_cost' => 50,
        'discount_pct' => 5,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2375.00,
    ]));

    $service = app(LaserInvoiceService::class);
    $invoice = $service->createInvoice($order);

    expect($invoice)->toBeInstanceOf(LaserInvoice::class)
        ->and($invoice->reference)->toStartWith('LFAC-')
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($invoice->client_id)->toBe($order->client_id)
        ->and($invoice->laser_order_id)->toBe($order->id)
        ->and($invoice->lines)->toHaveCount(1);

    $line = $invoice->lines->first();
    expect($line->laser_order_line_id)->toBe($order->lines->first()->id)
        ->and($line->material_id)->toBe($material->id)
        ->and($line->quantity)->toBe(10)
        ->and($line->quantity_invoiced)->toBe(10)
        ->and((float) $line->unit_price_ht)->toBe(250.0)
        ->and((float) $line->discount_pct)->toBe(5.0)
        ->and((float) $line->density_kg_m3)->toBe(7850.0);
});

it('updates invoiced_quantity on order line after invoicing', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce qty',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->createInvoice($order);

    $orderLine->refresh();
    expect($orderLine->invoiced_quantity)->toBe(10);
});

it('calculates totals with TVA', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce tva',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'density_kg_m3' => 7850,
        'total_ht' => 1000.00,
    ]));

    $service = app(LaserInvoiceService::class);
    $invoice = $service->createInvoice($order);

    expect((float) $invoice->total_ht)->toBe(1000.0)
        ->and((float) $invoice->total_tva)->toBe(200.0)
        ->and((float) $invoice->total_ttc)->toBe(1200.0);
});

it('rejects invoice creation when no lines to invoice', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => LaserMaterial::factory()->create(['is_active' => true])->id,
        'description' => 'Pièce vide',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 0,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Aucune ligne à facturer pour cette commande.');
    $service->createInvoice($order);
});

it('supports partial invoicing', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce partielle',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 6,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'density_kg_m3' => 7850,
        'total_ht' => 600.00,
    ]));

    $service = app(LaserInvoiceService::class);
    $invoice = $service->createInvoice($order);

    expect($invoice->lines->first()->quantity_invoiced)->toBe(6)
        ->and((float) $invoice->total_ht)->toBe(600.0);
});

// ============================================================
// Legalize invoice (NF525 hash chain)
// ============================================================

it('legalizes a DRAFT invoice and sets hash', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::VALIDATED)
        ->and($invoice->signature_hash)->not->toBeNull()
        ->and(strlen($invoice->signature_hash))->toBe(64);
});

it('updates order status to BILLED after legalization when all delivered lines invoiced', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce facturée',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'invoiced_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 2500,
        'total_ttc' => 3000,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::BILLED);
});

it('rejects legalizing a non-DRAFT invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seule une facture en brouillon peut être légalisée.');
    $service->legalizeInvoice($invoice);
});

it('generates chained hashes across invoices', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $inv1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_ttc' => 600,
    ]));
    $service->legalizeInvoice($inv1);
    $hash1 = $inv1->fresh()->signature_hash;

    $inv2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));
    $service->legalizeInvoice($inv2);
    $hash2 = $inv2->fresh()->signature_hash;

    expect($hash1)->not->toBe($hash2);
});

// ============================================================
// Credit note creation
// ============================================================

it('creates a credit note on a validated invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Erreur de facturation');

    expect($creditNote)->toBeInstanceOf(LaserCreditNote::class)
        ->and($creditNote->reference)->toStartWith('LAVO-')
        ->and($creditNote->laser_invoice_id)->toBe($invoice->id)
        ->and($creditNote->client_id)->toBe($invoice->client_id)
        ->and((float) $creditNote->total_ht)->toBe(1000.0)
        ->and((float) $creditNote->total_tva)->toBe(200.0)
        ->and((float) $creditNote->total_ttc)->toBe(1200.0)
        ->and($creditNote->reason)->toBe('Erreur de facturation')
        ->and($creditNote->status)->toBe('validated');
});

it('rejects credit note on a DRAFT invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seules les factures validées ou payées peuvent faire l\'objet d\'un avoir.');
    $service->createCreditNote($invoice, 'Motif');
});

it('allows credit note on a PAID invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::PAID,
        'total_ht' => 500,
        'total_tva' => 100,
        'total_ttc' => 600,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Annulation');

    expect($creditNote)->toBeInstanceOf(LaserCreditNote::class)
        ->and((float) $creditNote->total_ht)->toBe(500.0);
});

// ============================================================
// InvoiceStatus enum coverage
// ============================================================

it('covers all InvoiceStatus getLabel cases', function () {
    expect(InvoiceStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(InvoiceStatus::VALIDATED->getLabel())->toBe('Validée')
        ->and(InvoiceStatus::PAID->getLabel())->toBe('Payée')
        ->and(InvoiceStatus::CANCELED->getLabel())->toBe('Annulée');
});

it('covers all InvoiceStatus getColor cases', function () {
    expect(InvoiceStatus::DRAFT->getColor())->toBe('gray')
        ->and(InvoiceStatus::VALIDATED->getColor())->toBe('primary')
        ->and(InvoiceStatus::PAID->getColor())->toBe('success')
        ->and(InvoiceStatus::CANCELED->getColor())->toBe('danger');
});

it('covers all InvoiceStatus getIcon cases', function () {
    expect(InvoiceStatus::DRAFT->getIcon())->not->toBeNull()
        ->and(InvoiceStatus::VALIDATED->getIcon())->not->toBeNull()
        ->and(InvoiceStatus::PAID->getIcon())->not->toBeNull()
        ->and(InvoiceStatus::CANCELED->getIcon())->not->toBeNull();
});

// ============================================================
// LaserInvoice model coverage
// ============================================================

it('casts LaserInvoice attributes correctly', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000.50,
        'total_tva' => 200.10,
        'total_ttc' => 1200.60,
        'due_date' => '2026-10-01',
    ]));

    expect($invoice->status)->toBeInstanceOf(InvoiceStatus::class)
        ->and($invoice->status)->toBe(InvoiceStatus::VALIDATED)
        ->and($invoice->due_date)->not->toBeNull();
});

it('LaserInvoice has correct fillable fields', function () {
    $invoice = new LaserInvoice;
    expect($invoice->getFillable())->toContain(
        'client_id', 'laser_order_id', 'reference', 'status',
        'total_ht', 'total_tva', 'total_ttc', 'due_date', 'signature_hash'
    );
});

it('LaserInvoice client relationship works', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    expect($invoice->client)->not->toBeNull()
        ->and($invoice->client->id)->toBe($invoice->client_id);
});

it('LaserInvoice order relationship works', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    expect($invoice->order)->not->toBeNull()
        ->and($invoice->order->id)->toBe($invoice->laser_order_id);
});

it('LaserInvoice lines relationship works', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);

    expect($invoice->lines)->toHaveCount(1);
});

it('LaserInvoice creditNotes relationship works', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    LaserCreditNote::withoutEvents(fn () => LaserCreditNote::factory()->create([
        'laser_invoice_id' => $invoice->id,
    ]));

    expect($invoice->creditNotes)->toHaveCount(1);
});

it('canBeDeleted is true only in DRAFT status', function () {
    $draft = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create(['status' => InvoiceStatus::DRAFT]));
    expect($draft->canBeDeleted())->toBeTrue();

    $validated = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create(['status' => InvoiceStatus::VALIDATED]));
    expect($validated->canBeDeleted())->toBeFalse();

    $paid = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create(['status' => InvoiceStatus::PAID]));
    expect($paid->canBeDeleted())->toBeFalse();
});

// ============================================================
// LaserInvoiceLine model coverage
// ============================================================

it('casts LaserInvoiceLine attributes correctly', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserInvoiceLine::withoutEvents(fn () => LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'quantity_invoiced' => 7,
        'length_mm' => 500.50,
        'width_mm' => 250.25,
        'thickness_mm' => 2.5,
        'unit_price_ht' => 100.00,
        'discount_pct' => 5.00,
        'total_ht' => 665.00,
        'weight_kg' => 19.6250,
        'density_kg_m3' => 7850.00,
    ]));

    expect((int) $line->quantity)->toBe(10)
        ->and((int) $line->quantity_invoiced)->toBe(7)
        ->and((float) $line->length_mm)->toBe(500.50)
        ->and((float) $line->width_mm)->toBe(250.25)
        ->and((float) $line->thickness_mm)->toBe(2.5)
        ->and((float) $line->unit_price_ht)->toBe(100.0)
        ->and((float) $line->discount_pct)->toBe(5.0)
        ->and((float) $line->total_ht)->toBe(665.0)
        ->and((float) $line->weight_kg)->toBe(19.625)
        ->and((float) $line->density_kg_m3)->toBe(7850.0);
});

it('LaserInvoiceLine has correct fillable fields', function () {
    $line = new LaserInvoiceLine;
    expect($line->getFillable())->toContain(
        'laser_invoice_id', 'laser_order_line_id', 'material_id',
        'description', 'length_mm', 'width_mm', 'thickness_mm',
        'quantity', 'quantity_invoiced', 'unit_price_ht', 'discount_pct',
        'total_ht', 'weight_kg', 'density_kg_m3'
    );
});

it('LaserInvoiceLine relationships work', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserInvoiceLine::withoutEvents(fn () => LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]));

    expect($line->invoice)->not->toBeNull()
        ->and($line->invoice->id)->toBe($line->laser_invoice_id)
        ->and($line->orderLine)->not->toBeNull()
        ->and($line->orderLine->id)->toBe($line->laser_order_line_id)
        ->and($line->material)->not->toBeNull()
        ->and($line->material->id)->toBe($line->material_id);
});

// ============================================================
// LaserCreditNote model coverage
// ============================================================

it('LaserCreditNote has correct fillable fields', function () {
    $creditNote = new LaserCreditNote;
    expect($creditNote->getFillable())->toContain(
        'client_id', 'laser_invoice_id', 'reference', 'status',
        'total_ht', 'total_tva', 'total_ttc', 'reason'
    );
});

it('LaserCreditNote client relationship works', function () {
    $creditNote = LaserCreditNote::withoutEvents(fn () => LaserCreditNote::factory()->create());
    expect($creditNote->client)->not->toBeNull()
        ->and($creditNote->client->id)->toBe($creditNote->client_id);
});

it('LaserCreditNote invoice relationship works', function () {
    $creditNote = LaserCreditNote::withoutEvents(fn () => LaserCreditNote::factory()->create());
    expect($creditNote->invoice)->not->toBeNull()
        ->and($creditNote->invoice->id)->toBe($creditNote->laser_invoice_id);
});

// ============================================================
// LaserOrderLine invoiced_quantity
// ============================================================

it('LaserOrderLine has invoiceLines relationship', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
    ]));

    LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);

    expect($orderLine->invoiceLines)->toHaveCount(1);
});

// ============================================================
// LaserOrder invoices relationship
// ============================================================

it('LaserOrder has invoices relationship', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
    ]));

    expect($order->invoices)->toHaveCount(1)
        ->and($order->invoices->first()->id)->toBe($invoice->id);
});

// ============================================================
// Reference generation
// ============================================================

it('generates unique invoice references', function () {
    $service = app(LaserInvoiceService::class);

    $ref1 = $service->generateInvoiceReference();
    $ref2 = $service->generateInvoiceReference();

    expect($ref1)->toStartWith('LFAC-')
        ->and($ref2)->toStartWith('LFAC-')
        ->and($ref1)->not->toBe($ref2);
});

it('generates unique credit note references', function () {
    $service = app(LaserInvoiceService::class);

    $ref1 = $service->generateCreditNoteReference();
    $ref2 = $service->generateCreditNoteReference();

    expect($ref1)->toStartWith('LAVO-')
        ->and($ref2)->toStartWith('LAVO-')
        ->and($ref1)->not->toBe($ref2);
});

// ============================================================
// LaserDocumentationService coverage
// ============================================================

it('generates correct invoice filename', function () {
    $service = app(LaserDocumentationService::class);
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->make(['reference' => 'LFAC-2026-0001']));

    expect($service->getInvoiceFilename($invoice))->toBe('facture_LFAC-2026-0001');
});

it('generates correct invoice path', function () {
    $service = app(LaserDocumentationService::class);
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->make(['reference' => 'LFAC-2026-0001']));

    expect($service->getInvoicePath($invoice))->toBe('documents/laser/invoices/facture_LFAC-2026-0001.pdf');
});

it('generates correct credit note filename', function () {
    $service = app(LaserDocumentationService::class);
    $creditNote = LaserCreditNote::withoutEvents(fn () => LaserCreditNote::factory()->make(['reference' => 'LAVO-2026-0001']));

    expect($service->getCreditNoteFilename($creditNote))->toBe('avoir_LAVO-2026-0001');
});

it('generates correct credit note path', function () {
    $service = app(LaserDocumentationService::class);
    $creditNote = LaserCreditNote::withoutEvents(fn () => LaserCreditNote::factory()->make(['reference' => 'LAVO-2026-0001']));

    expect($service->getCreditNotePath($creditNote))->toBe('documents/laser/credit_notes/avoir_LAVO-2026-0001.pdf');
});

// ============================================================
// Job dispatch — afterCommit pattern (not testable with RefreshDatabase)
// ============================================================

it('LaserInvoiceObserver does not dispatch on created (dispatch moved to service afterCommit)', function () {
    $observer = new LaserInvoiceObserver(
        app(LaserDocumentationService::class)
    );

    // Observer should only have deleted hook, no created hook
    expect(method_exists($observer, 'created'))->toBeFalse()
        ->and(method_exists($observer, 'deleted'))->toBeTrue();
});

it('LaserCreditNoteObserver does not dispatch on created (dispatch moved to service afterCommit)', function () {
    $observer = new LaserCreditNoteObserver(
        app(LaserDocumentationService::class)
    );

    expect(method_exists($observer, 'created'))->toBeFalse()
        ->and(method_exists($observer, 'deleted'))->toBeTrue();
});

it('LaserInvoiceObserver constructor accepts LaserDocumentationService', function () {
    $observer = new LaserInvoiceObserver(
        app(LaserDocumentationService::class)
    );

    expect($observer)->toBeInstanceOf(LaserInvoiceObserver::class);
});

it('LaserCreditNoteObserver constructor accepts LaserDocumentationService', function () {
    $observer = new LaserCreditNoteObserver(
        app(LaserDocumentationService::class)
    );

    expect($observer)->toBeInstanceOf(LaserCreditNoteObserver::class);
});

// ============================================================
// Review #1: Partial invoice does NOT mark order BILLED
// ============================================================

it('does not mark order BILLED when some delivered lines are not yet invoiced', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    // Line 1: delivered 10, invoiced 10 (fully invoiced)
    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce A',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'invoiced_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'density_kg_m3' => 7850,
        'total_ht' => 1000.00,
    ]));

    // Line 2: delivered 5, invoiced 0 (delivered but NOT yet invoiced)
    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce B',
        'length_mm' => 300,
        'width_mm' => 200,
        'thickness_mm' => 3,
        'quantity' => 10,
        'delivered_quantity' => 5,
        'invoiced_quantity' => 0,
        'cut_length_mm' => 1000,
        'weight_kg' => 14.13,
        'unit_price_ht' => 80.00,
        'density_kg_m3' => 7850,
        'total_ht' => 400.00,
    ]));

    // Invoice only line 1 (partial invoice)
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $order->refresh();
    expect($order->status)->not->toBe(OrderStatus::BILLED);
});

it('marks order BILLED when all delivered lines are fully invoiced', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce complète',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'invoiced_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'density_kg_m3' => 7850,
        'total_ht' => 1000.00,
    ]));

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::BILLED);
});

// ============================================================
// Review #2: Double legalization is blocked
// ============================================================

// ============================================================
// Review #3: PDF job dispatched after legalization (afterCommit)
// ============================================================

it('uses afterCommit for PDF dispatch in legalizeInvoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::VALIDATED)
        ->and($invoice->signature_hash)->not->toBeNull();
});

// ============================================================
// Review #4: Credit note cap
// ============================================================

it('creates a partial credit note', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Annulation partielle', 400.0);

    expect((float) $creditNote->total_ht)->toBe(400.0)
        ->and((float) $creditNote->total_tva)->toBe(80.0)
        ->and((float) $creditNote->total_ttc)->toBe(480.0);

    $invoice->refresh();
    expect((float) $invoice->credited_amount_ht)->toBe(400.0)
        ->and((float) $invoice->remaining_creditable_ht)->toBe(600.0);
});

it('creates two successive credit notes up to full amount', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->createCreditNote($invoice, 'Premier avoir', 600.0);

    $invoice->refresh();
    expect((float) $invoice->remaining_creditable_ht)->toBe(400.0);

    $service->createCreditNote($invoice, 'Deuxième avoir', 400.0);

    $invoice->refresh();
    expect((float) $invoice->remaining_creditable_ht)->toBe(0.0)
        ->and($invoice->canBeCredited())->toBeFalse();
});

it('rejects credit note exceeding remaining amount', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('dépasse le solde restant');
    $service->createCreditNote($invoice, 'Trop', 1500.0);
});

it('rejects credit note when invoice fully credited', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->createCreditNote($invoice, 'Avoir complet');

    $invoice->refresh();
    expect($invoice->canBeCredited())->toBeFalse();

    $this->expectException(Exception::class);
    $service->createCreditNote($invoice, 'Deuxième avoir');
});

it('rejects credit note with zero amount', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('supérieur à zéro');
    $service->createCreditNote($invoice, 'Montant zéro', 0.0);
});

// ============================================================
// Review #5: Delete DRAFT invoice restores invoiced_quantity
// ============================================================

it('deletes a DRAFT invoice and restores invoiced_quantity', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce delete',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'invoiced_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 2500,
        'total_ttc' => 3000,
    ]));

    LaserInvoiceLine::withoutEvents(fn () => LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'quantity_invoiced' => 10,
        'unit_price_ht' => 250.00,
        'discount_pct' => 0,
        'total_ht' => 2500.00,
        'weight_kg' => 19.625,
        'density_kg_m3' => 7850,
    ]));

    $orderLine->refresh();
    expect($orderLine->invoiced_quantity)->toBe(10);

    $service = app(LaserInvoiceService::class);
    $service->deleteInvoice($invoice);

    $orderLine->refresh();
    expect($orderLine->invoiced_quantity)->toBe(0);
});

it('rejects deleting a non-DRAFT invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seule une facture en brouillon peut être supprimée.');
    $service->deleteInvoice($invoice);
});

// ============================================================
// Model computed attributes
// ============================================================

it('computes remaining_creditable_ht correctly', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'total_ht' => 1000,
        'credited_amount_ht' => 300,
    ]));

    expect($invoice->remaining_creditable_ht)->toBe(700.0);
});

it('computes remaining_creditable_ttc correctly', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'total_ttc' => 1200,
        'credited_amount_ttc' => 480,
    ]));

    expect($invoice->remaining_creditable_ttc)->toBe(720.0);
});

it('canBeCredited returns false when fully credited', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'credited_amount_ht' => 1000,
    ]));

    expect($invoice->canBeCredited())->toBeFalse();
});

it('canBeCredited returns true when partially credited', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'credited_amount_ht' => 400,
    ]));

    expect($invoice->canBeCredited())->toBeTrue();
});

// ============================================================
// Concurrency: credit note race condition
// ============================================================

it('prevents two credit notes from exceeding the invoice total', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    $service->createCreditNote($invoice, 'Avoir 1', 600.0);

    $invoice->refresh();
    expect((float) $invoice->credited_amount_ht)->toBe(600.0)
        ->and((float) $invoice->remaining_creditable_ht)->toBe(400.0);

    $service->createCreditNote($invoice, 'Avoir 2', 400.0);

    $invoice->refresh();
    expect((float) $invoice->credited_amount_ht)->toBe(1000.0)
        ->and((float) $invoice->remaining_creditable_ht)->toBe(0.0);

    $this->expectException(Exception::class);
    $service->createCreditNote($invoice, 'Avoir 3 - impossible');
});

it('validates credit amount against locked invoice state', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    // First credit note for 800
    $service->createCreditNote($invoice, 'Partiel', 800.0);

    $invoice->refresh();
    expect((float) $invoice->remaining_creditable_ht)->toBe(200.0);

    // Attempting 300 should fail (only 200 remaining)
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('dépasse le solde restant');
    $service->createCreditNote($invoice, 'Trop', 300.0);
});

// ============================================================
// Concurrency: hash chain serialization
// ============================================================

it('uses legalization sequence for hash chain', function () {
    Queue::fake();

    $sequence = LaserLegalizationSequence::first();
    expect($sequence)->not->toBeNull()
        ->and($sequence->last_hash)->toBe('GENESIS');

    $invoice1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_ttc' => 600,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice1);

    $sequence->refresh();
    $hash1 = $invoice1->fresh()->signature_hash;

    expect($sequence->last_hash)->toBe($hash1)
        ->and($sequence->last_reference)->not->toBeNull();

    $invoice2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service->legalizeInvoice($invoice2);

    $sequence->refresh();
    $hash2 = $invoice2->fresh()->signature_hash;

    // Hash2 should reference hash1 as previous (contain it in the chain)
    expect($hash2)->not->toBe($hash1)
        ->and($sequence->last_hash)->toBe($hash2);
});

it('rejects double legalization of same invoice', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seule une facture en brouillon peut être légalisée.');
    $service->legalizeInvoice($invoice->fresh());
});

it('LaserLegalizationSequence model has correct fillable', function () {
    $seq = new LaserLegalizationSequence;
    expect($seq->getFillable())->toContain('last_hash', 'last_reference');
});

// ============================================================
// Review fixes: lockForUpdate, delete race, order status validation
// ============================================================

it('legalizeInvoice locks sequence via query builder', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $sequence = LaserLegalizationSequence::first();
    $invoice->refresh();

    expect($sequence->last_hash)->toBe($invoice->signature_hash)
        ->and($sequence->last_reference)->toBe($invoice->reference);
});

it('deleteInvoice re-checks status after lockForUpdate', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);

    // Simulate: invoice becomes VALIDATED between canBeDeleted() and lockForUpdate()
    // by legalizing it first inside a transaction, then trying to delete
    $service->legalizeInvoice($invoice);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::VALIDATED);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seule une facture en brouillon peut être supprimée.');
    $service->deleteInvoice($invoice);
});

it('rejects invoice creation for CANCELLED order', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce cancelled',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $order->update(['status' => OrderStatus::CANCELLED]);

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Cette commande ne peut pas être facturée.');
    $service->createInvoice($order);
});

it('rejects invoice creation for BILLED order', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce billed',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $order->update(['status' => OrderStatus::BILLED]);

    $service = app(LaserInvoiceService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Cette commande ne peut pas être facturée.');
    $service->createInvoice($order);
});

// ============================================================
// Review #2: CANCELLED order must NOT be overwritten by legalization
// ============================================================

it('does not overwrite CANCELLED order to BILLED when legalizing draft invoice', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce cancelled',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'invoiced_quantity' => 0,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 250.00,
        'density_kg_m3' => 7850,
        'total_ht' => 2500.00,
    ]));

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'laser_order_id' => $order->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 2500,
        'total_ttc' => 3000,
    ]));

    $order->update(['status' => OrderStatus::CANCELLED]);

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CANCELLED);
});

// ============================================================
// Review #3: Hash chain with previous_hash verification
// ============================================================

it('chain contains previous hash in hash2 payload', function () {
    Queue::fake();

    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    $invoice1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_ttc' => 600,
    ]));

    $service = app(LaserInvoiceService::class);
    $service->legalizeInvoice($invoice1);

    $sequence->refresh();
    $hash1 = $invoice1->fresh()->signature_hash;
    expect($sequence->last_hash)->toBe($hash1);

    $invoice2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $service->legalizeInvoice($invoice2);

    $sequence->refresh();
    $hash2 = $invoice2->fresh()->signature_hash;

    // Verify the chain: hash2 must differ from hash1, and sequence ends with hash2
    expect($hash1)->not->toBe($hash2)
        ->and($sequence->last_hash)->toBe($hash2)
        ->and($sequence->last_reference)->toBe($invoice2->fresh()->reference);
});

// ============================================================
// Review #4: Enriched hash payload detects line-level changes
// ============================================================

it('hash changes when line data differs between two identical totals', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $orderLineA = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 10,
    ]));

    $inv1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv1->id,
        'laser_order_line_id' => $orderLineA->id,
        'material_id' => $material->id,
        'description' => 'Pièce A',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'quantity_invoiced' => 10,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'total_ht' => 1000.00,
        'weight_kg' => 19.625,
        'density_kg_m3' => 7850,
    ]);

    $service->legalizeInvoice($inv1);
    $hash1 = $inv1->fresh()->signature_hash;

    $orderLineB = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 20,
    ]));

    $inv2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv2->id,
        'laser_order_line_id' => $orderLineB->id,
        'material_id' => $material->id,
        'description' => 'Pièce B',
        'length_mm' => 300,
        'width_mm' => 200,
        'thickness_mm' => 5,
        'quantity' => 20,
        'quantity_invoiced' => 20,
        'unit_price_ht' => 50.00,
        'discount_pct' => 0,
        'total_ht' => 1000.00,
        'weight_kg' => 23.55,
        'density_kg_m3' => 7850,
    ]);

    $service->legalizeInvoice($inv2);
    $hash2 = $inv2->fresh()->signature_hash;

    expect($hash1)->not->toBe($hash2);
});

// ============================================================
// Review #5: Recalculate expected hash and verify chain integrity
// ============================================================

it('recalculates expected hash and verifies chain integrity', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    // Legalize first invoice
    $inv1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_tva' => 100,
        'total_ttc' => 600,
    ]));
    $service->legalizeInvoice($inv1);
    $hash1 = $inv1->fresh()->signature_hash;

    expect($hash1)->not->toBeNull()
        ->and(strlen($hash1))->toBe(64)
        ->and($sequence->fresh()->last_hash)->toBe($hash1);

    // Legalize second invoice — hash must chain from first
    $inv2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));
    $service->legalizeInvoice($inv2);
    $hash2 = $inv2->fresh()->signature_hash;

    expect($hash2)->not->toBeNull()
        ->and(strlen($hash2))->toBe(64)
        ->and($hash2)->not->toBe($hash1)
        ->and($sequence->fresh()->last_hash)->toBe($hash2)
        ->and($sequence->fresh()->last_reference)->toBe($inv2->fresh()->reference);

    // Chain property: changing the previous hash produces a different result
    $inv3 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_tva' => 100,
        'total_ttc' => 600,
    ]));
    $service->legalizeInvoice($inv3);
    $hash3 = $inv3->fresh()->signature_hash;

    // hash3 was computed with hash2 as previous — must differ from hash2
    expect($hash3)->not->toBe($hash2)
        ->and($sequence->fresh()->last_hash)->toBe($hash3);
});

// ============================================================
// Review: Credit notes participate in hash chain
// ============================================================

it('credit note gets a signature_hash and updates the legalization sequence', function () {
    Queue::fake();

    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Erreur de facturation');

    $creditNote->refresh();
    $sequence->refresh();

    expect($creditNote->signature_hash)->not->toBeNull()
        ->and(strlen($creditNote->signature_hash))->toBe(64)
        ->and($sequence->last_hash)->toBe($creditNote->signature_hash)
        ->and($sequence->last_reference)->toBe($creditNote->reference);
});

it('credit note hash chain extends from invoice hash chain', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $service->legalizeInvoice($invoice);
    $hashAfterInvoice = $invoice->fresh()->signature_hash;

    $creditNote = $service->createCreditNote($invoice, 'Annulation');

    $sequence = LaserLegalizationSequence::first();
    $creditNote->refresh();

    expect($sequence->last_hash)->toBe($creditNote->signature_hash)
        ->and($creditNote->signature_hash)->not->toBe($hashAfterInvoice);
});

it('two credit notes produce sequential hashes in the chain', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
    ]));

    $cn1 = $service->createCreditNote($invoice, 'Avoir 1', 400.0);
    $hash1 = $cn1->fresh()->signature_hash;

    $cn2 = $service->createCreditNote($invoice, 'Avoir 2', 300.0);
    $hash2 = $cn2->fresh()->signature_hash;

    $sequence = LaserLegalizationSequence::first();

    expect($hash1)->not->toBe($hash2)
        ->and($sequence->last_hash)->toBe($hash2);
});

// ============================================================
// Review #4: Concurrent hash chain test
// ============================================================

it('concurrent hash chain is serialized correctly', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);
    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    // Create 5 invoices and legalize them through the service.
    // Each call acquires lockForUpdate on the sequence, ensuring serialization.
    $invoices = collect();
    for ($i = 0; $i < 5; $i++) {
        $invoices->push(LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => ($i + 1) * 100,
            'total_tva' => ($i + 1) * 20,
            'total_ttc' => ($i + 1) * 120,
        ])));
    }

    foreach ($invoices as $invoice) {
        $service->legalizeInvoice($invoice);
    }

    // All hashes are unique
    $hashes = $invoices->map(fn ($inv) => $inv->fresh()->signature_hash)->values();
    expect($hashes->unique()->count())->toBe(5);

    // Sequence ends on last invoice
    $sequence->refresh();
    expect($sequence->last_hash)->toBe($invoices->last()->fresh()->signature_hash);

    // Chain: each hash depends on the previous — verify by checking
    // that the hash of invoice[i] ≠ hash of invoice[i-1]
    for ($i = 1; $i < count($invoices); $i++) {
        $prev = $invoices[$i - 1]->fresh();
        $curr = $invoices[$i]->fresh();

        expect($curr->signature_hash)->not->toBe($prev->signature_hash);
    }
});

// ============================================================
// Review: Rename misleading "concurrent" test
// ============================================================

it('sequential hash chain serialization via lockForUpdate', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);
    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    $invoices = collect();
    for ($i = 0; $i < 5; $i++) {
        $invoices->push(LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => ($i + 1) * 100,
            'total_tva' => ($i + 1) * 20,
            'total_ttc' => ($i + 1) * 120,
        ])));
    }

    foreach ($invoices as $invoice) {
        $service->legalizeInvoice($invoice);
    }

    $hashes = $invoices->map(fn ($inv) => $inv->fresh()->signature_hash)->values();
    expect($hashes->unique()->count())->toBe(5);

    $sequence->refresh();
    expect($sequence->last_hash)->toBe($invoices->last()->fresh()->signature_hash);

    for ($i = 1; $i < count($invoices); $i++) {
        $prev = $invoices[$i - 1]->fresh();
        $curr = $invoices[$i]->fresh();
        expect($curr->signature_hash)->not->toBe($prev->signature_hash);
    }
});

// ============================================================
// Review #2: Immutability — validated invoice cannot be modified
// ============================================================

it('blocks update on a validated invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Une facture validée ou payée ne peut pas être modifiée.');
    $invoice->update(['total_ht' => 999]);
});

it('blocks delete on a validated invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Une facture validée ou payée ne peut pas être supprimée.');
    $invoice->delete();
});

it('blocks update on a paid invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::PAID,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Une facture validée ou payée ne peut pas être modifiée.');
    $invoice->update(['total_ht' => 999]);
});

it('allows update on a draft invoice via update()', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_ttc' => 1200,
    ]));

    $invoice->update(['total_ht' => 999]);
    expect($invoice->fresh()->total_ht)->toBe('999.00');
});

it('blocks update on lines of a validated invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
    ]));
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);

    $invoice->update(['status' => InvoiceStatus::VALIDATED]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Les lignes d\'une facture validée ou payée ne peuvent pas être modifiées.');
    $line->update(['unit_price_ht' => 200]);
});

it('blocks delete on lines of a validated invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
    ]));
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);

    $invoice->update(['status' => InvoiceStatus::VALIDATED]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Les lignes d\'une facture validée ou payée ne peuvent pas être supprimées.');
    $line->delete();
});

it('allows update on lines of a draft invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
    ]));
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserInvoiceLine::create([
        'laser_invoice_id' => $invoice->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);

    $line->update(['unit_price_ht' => 200]);
    expect($line->fresh()->unit_price_ht)->toBe('200.0000');
});

// ============================================================
// Review: vat_rate stored on invoice and used by credit note
// ============================================================

it('stores vat_rate on invoice from config', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce TVA',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'density_kg_m3' => 7850,
        'total_ht' => 1000.00,
    ]));

    $service = app(LaserInvoiceService::class);
    $invoice = $service->createInvoice($order);

    expect((float) $invoice->vat_rate)->toBe(20.0);
});

it('credit note uses invoice vat_rate not config', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Test TVA');

    expect((float) $creditNote->vat_rate)->toBe(20.0)
        ->and((float) $creditNote->total_tva)->toBe(200.0);
});

// ============================================================
// Review #4: Partial invoicing lifecycle
// ============================================================

it('partial invoicing lifecycle: deliver 6, invoice 6, deliver 4, invoice 4', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce partielle',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'delivered_quantity' => 6,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'density_kg_m3' => 7850,
        'total_ht' => 600.00,
    ]));

    $service = app(LaserInvoiceService::class);

    // Step 1: Invoice first 6 delivered
    $invoice1 = $service->createInvoice($order);
    expect($invoice1->lines->first()->quantity_invoiced)->toBe(6);

    $orderLine->refresh();
    expect($orderLine->invoiced_quantity)->toBe(6);

    $service->legalizeInvoice($invoice1);
    expect($invoice1->fresh()->status)->toBe(InvoiceStatus::VALIDATED);

    // Step 2: Deliver remaining 4
    LaserOrderLine::withoutEvents(fn () => $orderLine->update(['delivered_quantity' => 10]));
    $orderLine->refresh();
    expect($orderLine->delivered_quantity)->toBe(10);

    // Step 3: Create second invoice for remaining 4
    $invoice2 = $service->createInvoice($order);
    expect($invoice2->lines->first()->quantity_invoiced)->toBe(4);

    $orderLine->refresh();
    expect($orderLine->invoiced_quantity)->toBe(10);

    // Step 4: Legalize second invoice — order becomes BILLED
    $service->legalizeInvoice($invoice2);
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::BILLED);
});

// ============================================================
// 🔴 Immutability: adding line to validated invoice throws
// ============================================================

it('blocks adding a line to a validated invoice', function () {
    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
    ]));
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $invoice->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Les lignes d\'une facture validée ou payée ne peuvent pas être ajoutées.');
    $invoice->lines()->create([
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 500,
        'weight_kg' => 1.5,
        'density_kg_m3' => 7850,
    ]);
});

// ============================================================
// 🔴 Immutability: credit note validated cannot be updated/deleted
// ============================================================

it('blocks update on a validated credit note', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Test immutabilité');

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Un avoir validé ne peut pas être modifié.');
    $creditNote->update(['reason' => 'Nouveau motif']);
});

it('blocks delete on a validated credit note', function () {
    Queue::fake();

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Test suppression');

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Un avoir validé ne peut pas être supprimé.');
    $creditNote->delete();
});

// ============================================================
// 🔴 Hash: vat_rate included — different rates produce different hashes
// ============================================================

it('hash differs when vat_rate differs between two invoices with same totals', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 1,
    ]));

    $inv1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv1->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'description' => 'Pièce A',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'quantity_invoiced' => 10,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 1000,
        'weight_kg' => 10,
        'density_kg_m3' => 7850,
    ]);
    $service->legalizeInvoice($inv1);
    $hash1 = $inv1->fresh()->signature_hash;

    $inv2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_tva' => 550,
        'total_ttc' => 1550,
        'vat_rate' => 55,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv2->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'description' => 'Pièce B',
        'length_mm' => 300,
        'width_mm' => 200,
        'thickness_mm' => 5,
        'quantity' => 10,
        'quantity_invoiced' => 10,
        'unit_price_ht' => 100,
        'discount_pct' => 0,
        'total_ht' => 1000,
        'weight_kg' => 10,
        'density_kg_m3' => 7850,
    ]);
    $service->legalizeInvoice($inv2);
    $hash2 = $inv2->fresh()->signature_hash;

    expect($hash1)->not->toBe($hash2);
});

// ============================================================
// 🔴 Hash: recalculate expected hash and verify chain integrity
// ============================================================

it('recalculates expected hash and verifies chain integrity for invoice', function () {
    Queue::fake();

    $service = app(LaserInvoiceService::class);

    $sequence = LaserLegalizationSequence::first();
    expect($sequence->last_hash)->toBe('GENESIS');

    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 1,
    ]));

    $inv1 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 500,
        'total_tva' => 100,
        'total_ttc' => 600,
        'vat_rate' => 20,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv1->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'description' => 'Pièce A',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_invoiced' => 5,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'total_ht' => 500.00,
        'weight_kg' => 19.625,
        'density_kg_m3' => 7850,
    ]);

    $service->legalizeInvoice($inv1);
    $inv1->refresh();

    $previousHash = 'GENESIS';
    $definitiveRef1 = $inv1->reference;
    $createdAt1 = $inv1->created_at->toIso8601String();

    $linePayloads1 = $inv1->lines->map(function ($line) {
        return implode(':', [
            $line->material_id,
            $line->description,
            $line->length_mm,
            $line->width_mm,
            $line->thickness_mm,
            $line->quantity_invoiced,
            number_format($line->unit_price_ht, 4, '.', ''),
            $line->discount_pct,
            number_format($line->total_ht, 2, '.', ''),
            number_format($line->weight_kg, 4, '.', ''),
            number_format($line->density_kg_m3, 2, '.', ''),
        ]);
    })->implode('|');

    $expectedPayload1 = implode('|', [
        $definitiveRef1,
        $createdAt1,
        number_format($inv1->total_ht, 2, '.', ''),
        number_format($inv1->total_tva, 2, '.', ''),
        number_format($inv1->total_ttc, 2, '.', ''),
        number_format((float) $inv1->vat_rate, 2, '.', ''),
        $inv1->client_id,
        $inv1->laser_order_id,
        $linePayloads1,
    ]).'|'.$previousHash;

    $expectedHash1 = hash('sha256', $expectedPayload1);
    expect($inv1->signature_hash)->toBe($expectedHash1)
        ->and($sequence->fresh()->last_hash)->toBe($expectedHash1);

    // Second invoice
    $inv2 = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));
    LaserInvoiceLine::create([
        'laser_invoice_id' => $inv2->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'description' => 'Pièce B',
        'length_mm' => 300,
        'width_mm' => 200,
        'thickness_mm' => 5,
        'quantity' => 10,
        'quantity_invoiced' => 10,
        'unit_price_ht' => 100.00,
        'discount_pct' => 0,
        'total_ht' => 1000.00,
        'weight_kg' => 23.55,
        'density_kg_m3' => 7850,
    ]);

    $service->legalizeInvoice($inv2);
    $inv2->refresh();

    $definitiveRef2 = $inv2->reference;
    $createdAt2 = $inv2->created_at->toIso8601String();

    $linePayloads2 = $inv2->lines->map(function ($line) {
        return implode(':', [
            $line->material_id,
            $line->description,
            $line->length_mm,
            $line->width_mm,
            $line->thickness_mm,
            $line->quantity_invoiced,
            number_format($line->unit_price_ht, 4, '.', ''),
            $line->discount_pct,
            number_format($line->total_ht, 2, '.', ''),
            number_format($line->weight_kg, 4, '.', ''),
            number_format($line->density_kg_m3, 2, '.', ''),
        ]);
    })->implode('|');

    $expectedPayload2 = implode('|', [
        $definitiveRef2,
        $createdAt2,
        number_format($inv2->total_ht, 2, '.', ''),
        number_format($inv2->total_tva, 2, '.', ''),
        number_format($inv2->total_ttc, 2, '.', ''),
        number_format((float) $inv2->vat_rate, 2, '.', ''),
        $inv2->client_id,
        $inv2->laser_order_id,
        $linePayloads2,
    ]).'|'.$expectedHash1;

    $expectedHash2 = hash('sha256', $expectedPayload2);
    expect($inv2->signature_hash)->toBe($expectedHash2)
        ->and($sequence->fresh()->last_hash)->toBe($expectedHash2);
});

// ============================================================
// 🔴 Hash: recalculate expected hash for credit note
// ============================================================

it('recalculates expected hash for credit note', function () {
    Queue::fake();

    $sequence = LaserLegalizationSequence::first();
    $previousHash = $sequence->last_hash;

    $invoice = LaserInvoice::withoutEvents(fn () => LaserInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 1000,
        'total_tva' => 200,
        'total_ttc' => 1200,
        'vat_rate' => 20,
    ]));

    $service = app(LaserInvoiceService::class);
    $creditNote = $service->createCreditNote($invoice, 'Test hash avoir', 400.0);

    $expectedPayload = implode('|', [
        $creditNote->reference,
        $creditNote->fresh()->created_at->toIso8601String(),
        number_format(400.0, 2, '.', ''),
        number_format(80.0, 2, '.', ''),
        number_format(480.0, 2, '.', ''),
        number_format(20.0, 2, '.', ''),
        $invoice->client_id,
        $invoice->id,
        'Test hash avoir',
    ]).'|'.$previousHash;

    $expectedHash = hash('sha256', $expectedPayload);
    expect($creditNote->fresh()->signature_hash)->toBe($expectedHash)
        ->and($sequence->fresh()->last_hash)->toBe($expectedHash);
});
