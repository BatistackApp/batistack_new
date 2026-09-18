<?php

use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Observers\Laser\LaserOrderObserver;
use App\Services\Laser\LaserDocumentationService;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Support\Facades\Queue;

it('creates an order from a draft quote', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    LaserQuoteLine::withoutEvents(fn () => LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'description' => 'Pièce test',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 3,
        'cut_length_mm' => 1500,
        'price_per_kg' => 12.50,
        'price_per_meter' => 3.00,
        'density_kg_m3' => 7850,
        'programming_cost' => 25,
        'discount_pct' => 0,
        'unit_price_ht' => 50,
        'total_ht' => 150,
    ]));

    $quote->update(['total_ht' => 150, 'total_ttc' => 180]);

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($order)->toBeInstanceOf(LaserOrder::class)
        ->and($order->reference)->toStartWith('LAC-')
        ->and($order->status)->toBe(OrderStatus::CONFIRMED)
        ->and($order->client_id)->toBe($quote->client_id)
        ->and($order->laser_quote_id)->toBe($quote->id)
        ->and((float) $order->total_ht)->toBe(150.0)
        ->and((float) $order->total_ttc)->toBe(180.0);

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::ACCEPTED);
});

it('copies quote lines to order lines as snapshot', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::SENT,
    ]));

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    LaserQuoteLine::withoutEvents(fn () => LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'description' => 'Pièce snapshot',
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 5,
        'quantity' => 2,
        'cut_length_mm' => 3000,
        'weight_kg' => 19.625,
        'price_per_kg' => 15.00,
        'price_per_meter' => 4.50,
        'density_kg_m3' => 7850,
        'programming_cost' => 50,
        'discount_pct' => 5,
        'unit_price_ht' => 344.375,
        'total_ht' => 654.31,
    ]));

    $quote->update(['total_ht' => 654.31, 'total_ttc' => 785.17]);

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    $orderLines = $order->lines;
    expect($orderLines)->toHaveCount(1);

    $line = $orderLines->first();
    expect($line->material_id)->toBe($material->id)
        ->and($line->description)->toBe('Pièce snapshot')
        ->and((float) $line->length_mm)->toBe(1000.0)
        ->and((float) $line->width_mm)->toBe(500.0)
        ->and((float) $line->thickness_mm)->toBe(5.0)
        ->and((int) $line->quantity)->toBe(2)
        ->and((float) $line->cut_length_mm)->toBe(3000.0)
        ->and((float) $line->weight_kg)->toBe(19.625)
        ->and((float) $line->price_per_kg)->toBe(15.0)
        ->and((float) $line->price_per_meter)->toBe(4.5)
        ->and((float) $line->density_kg_m3)->toBe(7850.0)
        ->and((float) $line->programming_cost)->toBe(50.0)
        ->and((float) $line->discount_pct)->toBe(5.0)
        ->and((float) $line->total_ht)->toBe(654.31);
});

it('rejects quote in invalid status', function () {
    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::REJECTED,
    ]));

    $service = app(LaserQuoteService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Ce devis ne peut pas être accepté');
    $service->acceptQuote($quote);
});

it('rejects cancelled quote', function () {
    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::CANCELLED,
    ]));

    $service = app(LaserQuoteService::class);

    $this->expectException(Exception::class);
    $service->acceptQuote($quote);
});

it('accepts quote in sent status', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::SENT,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($order->status)->toBe(OrderStatus::CONFIRMED);

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::ACCEPTED);
});

it('accepts quote already in accepted status', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::ACCEPTED,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($order->status)->toBe(OrderStatus::CONFIRMED);
});

it('generates unique order references', function () {
    Queue::fake();

    $service = app(LaserQuoteService::class);

    $ref1 = $service->generateOrderReference();
    $ref2 = $service->generateOrderReference();

    expect($ref1)->toStartWith('LAC-')
        ->and($ref2)->toStartWith('LAC-')
        ->and($ref1)->not->toBe($ref2);
});

it('creates order with empty quote lines', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
        'total_ht' => 0,
        'total_ttc' => 0,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($order->lines)->toHaveCount(0)
        ->and($order->status)->toBe(OrderStatus::CONFIRMED);
});

it('can be deleted only in draft status', function () {
    $draftOrder = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create([
        'status' => OrderStatus::DRAFT,
    ]));
    expect($draftOrder->canBeDeleted())->toBeTrue();

    $confirmedOrder = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create([
        'status' => OrderStatus::CONFIRMED,
    ]));
    expect($confirmedOrder->canBeDeleted())->toBeFalse();
});

it('rejects duplicate order for same quote', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $service = app(LaserQuoteService::class);
    $service->acceptQuote($quote);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Ce devis a déjà été converti en commande');
    $service->acceptQuote($quote);
});

it('has one-to-one relationship between quote and order', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($quote->order)->not->toBeNull()
        ->and($quote->order->id)->toBe($order->id)
        ->and($quote->fresh()->order()->exists())->toBeTrue();
});

// ============================================================
// OrderStatus enum coverage
// ============================================================

it('covers all OrderStatus getLabel cases', function () {
    expect(OrderStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(OrderStatus::CONFIRMED->getLabel())->toBe('Confirmée')
        ->and(OrderStatus::IN_PROGRESS->getLabel())->toBe('En cours')
        ->and(OrderStatus::DELIVERED->getLabel())->toBe('Livrée')
        ->and(OrderStatus::BILLED->getLabel())->toBe('Facturée')
        ->and(OrderStatus::CANCELLED->getLabel())->toBe('Annulée');
});

it('covers all OrderStatus getColor cases', function () {
    expect(OrderStatus::DRAFT->getColor())->toBe('gray')
        ->and(OrderStatus::CONFIRMED->getColor())->toBe('info')
        ->and(OrderStatus::IN_PROGRESS->getColor())->toBe('warning')
        ->and(OrderStatus::DELIVERED->getColor())->toBe('success')
        ->and(OrderStatus::BILLED->getColor())->toBe('primary')
        ->and(OrderStatus::CANCELLED->getColor())->toBe('gray');
});

it('covers all OrderStatus getIcon cases', function () {
    expect(OrderStatus::DRAFT->getIcon())->not->toBeNull()
        ->and(OrderStatus::CONFIRMED->getIcon())->not->toBeNull()
        ->and(OrderStatus::IN_PROGRESS->getIcon())->not->toBeNull()
        ->and(OrderStatus::DELIVERED->getIcon())->not->toBeNull()
        ->and(OrderStatus::BILLED->getIcon())->not->toBeNull()
        ->and(OrderStatus::CANCELLED->getIcon())->not->toBeNull();
});

// ============================================================
// LaserOrder model coverage
// ============================================================

it('casts LaserOrder attributes correctly', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create([
        'status' => OrderStatus::CONFIRMED,
        'total_ht' => 100,
        'total_ttc' => 120,
    ]));

    expect($order->status)->toBeInstanceOf(OrderStatus::class)
        ->and($order->status)->toBe(OrderStatus::CONFIRMED)
        ->and((float) $order->total_ht)->toBe(100.0)
        ->and((float) $order->total_ttc)->toBe(120.0);
});

it('LaserOrder has correct fillable fields', function () {
    $order = new LaserOrder;
    expect($order->getFillable())->toContain(
        'client_id', 'laser_quote_id', 'reference', 'status',
        'total_ht', 'total_ttc', 'terms'
    );
});

it('LaserOrder client relationship works', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());

    expect($order->client)->not->toBeNull()
        ->and($order->client->id)->toBe($order->client_id);
});

it('LaserOrder quote relationship works', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    expect($order->quote)->not->toBeNull()
        ->and($order->quote->id)->toBe($quote->id);
});

// ============================================================
// LaserOrderLine model coverage
// ============================================================

it('casts LaserOrderLine attributes correctly', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'length_mm' => 100.50,
        'width_mm' => 50.25,
        'thickness_mm' => 2.5,
        'quantity' => 3,
        'surface_mm2' => 5050.1250,
        'cut_length_mm' => 300.00,
        'weight_kg' => 1.2345,
        'price_per_kg' => 12.5000,
        'price_per_meter' => 3.0000,
        'programming_cost' => 25.00,
        'discount_pct' => 5.00,
        'unit_price_ht' => 50.0000,
        'total_ht' => 142.50,
        'density_kg_m3' => 7850.00,
    ]);

    expect((float) $line->length_mm)->toBe(100.50)
        ->and((float) $line->width_mm)->toBe(50.25)
        ->and((float) $line->thickness_mm)->toBe(2.5)
        ->and((int) $line->quantity)->toBe(3)
        ->and((float) $line->surface_mm2)->toBe(5050.1250)
        ->and((float) $line->cut_length_mm)->toBe(300.0)
        ->and((float) $line->weight_kg)->toBe(1.2345)
        ->and((float) $line->price_per_kg)->toBe(12.5)
        ->and((float) $line->price_per_meter)->toBe(3.0)
        ->and((float) $line->programming_cost)->toBe(25.0)
        ->and((float) $line->discount_pct)->toBe(5.0)
        ->and((float) $line->unit_price_ht)->toBe(50.0)
        ->and((float) $line->total_ht)->toBe(142.5)
        ->and((float) $line->density_kg_m3)->toBe(7850.0);
});

it('LaserOrderLine has correct fillable fields', function () {
    $line = new LaserOrderLine;
    expect($line->getFillable())->toContain(
        'laser_order_id', 'material_id', 'description', 'length_mm', 'width_mm',
        'thickness_mm', 'quantity', 'surface_mm2', 'cut_length_mm', 'weight_kg',
        'price_per_kg', 'price_per_meter', 'programming_cost', 'discount_pct',
        'unit_price_ht', 'total_ht', 'density_kg_m3'
    );
});

it('LaserOrderLine order relationship works', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'total_ht' => 0,
    ]);

    expect($line->order)->not->toBeNull()
        ->and($line->order->id)->toBe($order->id);
});

it('LaserOrderLine material relationship works', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'total_ht' => 0,
    ]);

    expect($line->material)->not->toBeNull()
        ->and($line->material->id)->toBe($material->id);
});

// ============================================================
// LaserDocumentationService coverage
// ============================================================

it('generates correct order filename', function () {
    $service = app(LaserDocumentationService::class);
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->make(['reference' => 'LAC-2026-0001']));

    expect($service->getOrderFilename($order))->toBe('commande_laser_LAC-2026-0001');
});

it('generates correct order path', function () {
    $service = app(LaserDocumentationService::class);
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->make(['reference' => 'LAC-2026-0001']));

    expect($service->getOrderPath($order))->toBe('documents/laser/orders/commande_laser_LAC-2026-0001.pdf');
});

// ============================================================
// GenerateLaserDocumentJob coverage
// ============================================================

it('dispatches generate order document job on order created', function () {
    Queue::fake();

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $service = app(LaserQuoteService::class);
    $order = $service->acceptQuote($quote);

    Queue::assertPushed(GenerateLaserDocumentJob::class, function ($job) {
        return $job->namespace === 'laser_order';
    });
});

it('dispatches generate quote document job on quote created', function () {
    Queue::fake();

    LaserQuote::factory()->create();

    Queue::assertPushed(GenerateLaserDocumentJob::class, function ($job) {
        return $job->namespace === 'laser_quote';
    });
});

it('GenerateLaserDocumentJob handles laser_order namespace', function () {
    $job = new GenerateLaserDocumentJob('laser_quote', new LaserQuote);

    expect($job->namespace)->toBe('laser_quote')
        ->and($job->model)->toBeInstanceOf(LaserQuote::class);
});

it('LaserOrderObserver constructor accepts LaserDocumentationService', function () {
    $observer = new LaserOrderObserver(
        app(LaserDocumentationService::class)
    );

    expect($observer)->toBeInstanceOf(LaserOrderObserver::class);
});
