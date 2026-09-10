<?php

use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
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

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Ce devis ne peut pas être accepté');
    $service->acceptQuote($quote);
});

it('rejects cancelled quote', function () {
    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::CANCELLED,
    ]));

    $service = app(LaserQuoteService::class);

    $this->expectException(\Exception::class);
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
