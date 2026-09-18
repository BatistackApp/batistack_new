<?php

use App\Enums\Laser\DeliveryStatus;
use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserDeliveryNoteLine;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuote;
use App\Observers\Laser\LaserDeliveryNoteObserver;
use App\Services\Laser\LaserDeliveryNoteService;
use App\Services\Laser\LaserDocumentationService;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Support\Facades\Queue;

// ============================================================
// Delivery creation from order
// ============================================================

it('creates a delivery note from an order', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce livraison',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'price_per_kg' => 12.50,
        'price_per_meter' => 3.00,
        'density_kg_m3' => 7850,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    expect($delivery)->toBeInstanceOf(LaserDeliveryNote::class)
        ->and($delivery->reference)->toStartWith('LBL-')
        ->and($delivery->status)->toBe(DeliveryStatus::DRAFT)
        ->and($delivery->client_id)->toBe($order->client_id)
        ->and($delivery->laser_order_id)->toBe($order->id)
        ->and($delivery->lines)->toHaveCount(1);

    $line = $delivery->lines->first();
    expect($line->laser_order_line_id)->toBe($order->lines->first()->id)
        ->and($line->material_id)->toBe($material->id)
        ->and($line->quantity)->toBe(10)
        ->and($line->quantity_delivered)->toBe(10)
        ->and((float) $line->length_mm)->toBe(500.0)
        ->and((float) $line->weight_kg)->toBe(19.625);
});

it('reserves quantity when creating a delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce réservation',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 20,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 1000,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $orderLine->refresh();
    expect($orderLine->reserved_quantity)->toBe(20)
        ->and($orderLine->remaining_quantity)->toBe(0);
});

it('prevents over-delivery via multiple DRAFT BLs', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce over',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);

    $blA = $service->createDeliveryNote($order);
    expect($blA->lines->first()->quantity_delivered)->toBe(10);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Aucune ligne à livrer');
    $service->createDeliveryNote($order);
});

it('allows sequential partial deliveries with proper reservation', function () {
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
        'quantity' => 20,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 1000,
    ]));

    $service = app(LaserDeliveryNoteService::class);

    $blA = $service->createDeliveryNote($order);
    $blA->lines->first()->update(['quantity_delivered' => 12]);
    $service->shipDeliveryNote($blA);

    $orderLine->refresh();
    expect($orderLine->delivered_quantity)->toBe(12)
        ->and($orderLine->reserved_quantity)->toBe(0)
        ->and($orderLine->remaining_quantity)->toBe(8);

    $blB = $service->createDeliveryNote($order);
    expect($blB->lines)->toHaveCount(1)
        ->and($blB->lines->first()->quantity_delivered)->toBe(8);

    $service->shipDeliveryNote($blB);

    $orderLine->refresh();
    expect($orderLine->delivered_quantity)->toBe(20)
        ->and($orderLine->reserved_quantity)->toBe(0)
        ->and($orderLine->remaining_quantity)->toBe(0);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::DELIVERED);
});

it('rejects delivery creation when no remaining quantity', function () {
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
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'delivered_quantity' => 5,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Aucune ligne à livrer');
    $service->createDeliveryNote($order);
});

// ============================================================
// Ship delivery note
// ============================================================

it('ships a delivery note and updates delivered_quantity', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce expédition',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    expect($delivery->status)->toBe(DeliveryStatus::DRAFT);

    $service->shipDeliveryNote($delivery);

    $orderLine->refresh();
    expect($orderLine->delivered_quantity)->toBe(10)
        ->and($orderLine->reserved_quantity)->toBe(0);

    $delivery->refresh();
    expect($delivery->status)->toBe(DeliveryStatus::SHIPPED);
});

it('marks order as DELIVERED when all lines fully delivered', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce finale',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);
    $service->shipDeliveryNote($delivery);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::DELIVERED);
});

it('does not mark order as DELIVERED when partial delivery', function () {
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
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $delivery->lines->first()->update(['quantity_delivered' => 5]);
    $service->shipDeliveryNote($delivery);

    $order->refresh();
    expect($order->status)->not->toBe(OrderStatus::DELIVERED);
});

// ============================================================
// Ship idempotency (review fix #1)
// ============================================================

it('rejects shipping an already shipped delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce double ship',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $service->shipDeliveryNote($delivery);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Ce bon de livraison a déjà été expédié');
    $service->shipDeliveryNote($delivery);
});

it('rejects shipping a delivered delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce ship delivered',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);
    $service->shipDeliveryNote($delivery);

    $delivery->refresh();
    $service->receiveDeliveryNote($delivery);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Ce bon de livraison a déjà été expédié');
    $service->shipDeliveryNote($delivery);
});

// ============================================================
// Quantity validation (review fix #4)
// ============================================================

it('rejects shipping with zero quantity_delivered', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce zero qty',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    LaserDeliveryNoteLine::withoutEvents(fn () => $delivery->lines->first()->update(['quantity_delivered' => 0]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('La quantité livrée doit être supérieure à zéro');
    $service->shipDeliveryNote($delivery);
});

it('rejects shipping with quantity exceeding available', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce over qty',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    LaserDeliveryNoteLine::withoutEvents(fn () => $delivery->lines->first()->update(['quantity_delivered' => 15]));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('La quantité livrée dépasse la quantité disponible');
    $service->shipDeliveryNote($delivery);
});

// ============================================================
// Receive delivery note
// ============================================================

it('receives a delivery note and sets delivery_date', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce réception',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);
    $service->shipDeliveryNote($delivery);

    expect($delivery->refresh()->delivery_date)->toBeNull();

    $service->receiveDeliveryNote($delivery);

    $delivery->refresh();
    expect($delivery->status)->toBe(DeliveryStatus::DELIVERED)
        ->and($delivery->delivery_date)->not->toBeNull()
        ->and($delivery->delivery_date->isToday())->toBeTrue();
});

// ============================================================
// Receive state guard (review fix #3)
// ============================================================

it('rejects receiving a DRAFT delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce receive draft',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seul un bon de livraison expédié peut être réceptionné');
    $service->receiveDeliveryNote($delivery);
});

it('rejects receiving an already delivered delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce receive delivered',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);
    $service->shipDeliveryNote($delivery);

    $delivery->refresh();
    $service->receiveDeliveryNote($delivery);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seul un bon de livraison expédié peut être réceptionné');
    $service->receiveDeliveryNote($delivery);
});

// ============================================================
// Delete delivery note
// ============================================================

it('deletes a DRAFT delivery note and releases reserved quantity', function () {
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
        'quantity' => 15,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $orderLine->refresh();
    expect($orderLine->reserved_quantity)->toBe(15)
        ->and($orderLine->remaining_quantity)->toBe(0);

    $service->deleteDeliveryNote($delivery);

    $orderLine->refresh();
    expect($orderLine->reserved_quantity)->toBe(0)
        ->and($orderLine->remaining_quantity)->toBe(15);
});

it('rejects deleting a shipped delivery note', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce delete shipped',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 5,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);
    $service->shipDeliveryNote($delivery);

    $delivery->refresh();

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Seul un bon de livraison brouillon peut être supprimé');
    $service->deleteDeliveryNote($delivery);
});

// ============================================================
// Observer quantity validation (review fix)
// ============================================================

it('rejects setting quantity_delivered to zero on BL line', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce zero obs',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('La quantité livrée doit être supérieure à zéro');
    $delivery->lines->first()->update(['quantity_delivered' => 0]);
});

it('rejects setting quantity_delivered to negative on BL line', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce neg obs',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('La quantité livrée doit être supérieure à zéro');
    $delivery->lines->first()->update(['quantity_delivered' => -3]);
});

it('rejects setting quantity_delivered exceeding available on BL line', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce exceed obs',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('dépasse la quantité disponible');
    $delivery->lines->first()->update(['quantity_delivered' => 15]);
});

it('allows reducing quantity_delivered back to a valid value', function () {
    Queue::fake();

    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $quote = LaserQuote::withoutEvents(fn () => LaserQuote::factory()->create([
        'status' => QuoteStatus::DRAFT,
    ]));

    $order = app(LaserQuoteService::class)->acceptQuote($quote);

    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'description' => 'Pièce valid obs',
        'length_mm' => 500,
        'width_mm' => 250,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 1500,
        'weight_kg' => 19.625,
        'total_ht' => 500,
    ]));

    $service = app(LaserDeliveryNoteService::class);
    $delivery = $service->createDeliveryNote($order);

    $delivery->lines->first()->update(['quantity_delivered' => 6]);

    $orderLine->refresh();
    expect($orderLine->reserved_quantity)->toBe(6);

    $delivery->lines->first()->update(['quantity_delivered' => 4]);

    $orderLine->refresh();
    expect($orderLine->reserved_quantity)->toBe(4);
});

// ============================================================
// DeliveryStatus enum coverage
// ============================================================

it('covers all DeliveryStatus getLabel cases', function () {
    expect(DeliveryStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(DeliveryStatus::SHIPPED->getLabel())->toBe('Expédié')
        ->and(DeliveryStatus::DELIVERED->getLabel())->toBe('Réceptionné');
});

it('covers all DeliveryStatus getColor cases', function () {
    expect(DeliveryStatus::DRAFT->getColor())->toBe('gray')
        ->and(DeliveryStatus::SHIPPED->getColor())->toBe('info')
        ->and(DeliveryStatus::DELIVERED->getColor())->toBe('success');
});

it('covers all DeliveryStatus getIcon cases', function () {
    expect(DeliveryStatus::DRAFT->getIcon())->not->toBeNull()
        ->and(DeliveryStatus::SHIPPED->getIcon())->not->toBeNull()
        ->and(DeliveryStatus::DELIVERED->getIcon())->not->toBeNull();
});

// ============================================================
// LaserDeliveryNote model coverage
// ============================================================

it('casts LaserDeliveryNote attributes correctly', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create([
        'status' => DeliveryStatus::SHIPPED,
        'delivery_date' => '2026-09-15',
    ]));

    expect($delivery->status)->toBeInstanceOf(DeliveryStatus::class)
        ->and($delivery->status)->toBe(DeliveryStatus::SHIPPED)
        ->and($delivery->delivery_date)->not->toBeNull();
});

it('LaserDeliveryNote has correct fillable fields', function () {
    $delivery = new LaserDeliveryNote;
    expect($delivery->getFillable())->toContain(
        'client_id', 'laser_order_id', 'reference', 'status', 'delivery_date'
    );
});

it('LaserDeliveryNote client relationship works', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create());
    expect($delivery->client)->not->toBeNull()
        ->and($delivery->client->id)->toBe($delivery->client_id);
});

it('LaserDeliveryNote order relationship works', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create());
    expect($delivery->order)->not->toBeNull()
        ->and($delivery->order->id)->toBe($delivery->laser_order_id);
});

it('LaserDeliveryNote lines relationship works', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $delivery->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    LaserDeliveryNoteLine::create([
        'laser_delivery_note_id' => $delivery->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_delivered' => 5,
        'cut_length_mm' => 300,
        'weight_kg' => 1.5,
    ]);

    expect($delivery->lines)->toHaveCount(1);
});

it('canBeDeleted is true only in DRAFT status', function () {
    $draft = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create(['status' => DeliveryStatus::DRAFT]));
    expect($draft->canBeDeleted())->toBeTrue();

    $shipped = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create(['status' => DeliveryStatus::SHIPPED]));
    expect($shipped->canBeDeleted())->toBeFalse();

    $delivered = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create(['status' => DeliveryStatus::DELIVERED]));
    expect($delivered->canBeDeleted())->toBeFalse();
});

// ============================================================
// LaserDeliveryNoteLine model coverage
// ============================================================

it('casts LaserDeliveryNoteLine attributes correctly', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $delivery->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserDeliveryNoteLine::withoutEvents(fn () => LaserDeliveryNoteLine::create([
        'laser_delivery_note_id' => $delivery->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'quantity_delivered' => 7,
        'length_mm' => 500.50,
        'width_mm' => 250.25,
        'thickness_mm' => 2.5,
        'cut_length_mm' => 1500.00,
        'weight_kg' => 19.6250,
        'density_kg_m3' => 7850.00,
    ]));

    expect((int) $line->quantity)->toBe(10)
        ->and((int) $line->quantity_delivered)->toBe(7)
        ->and((float) $line->length_mm)->toBe(500.50)
        ->and((float) $line->width_mm)->toBe(250.25)
        ->and((float) $line->thickness_mm)->toBe(2.5)
        ->and((float) $line->cut_length_mm)->toBe(1500.0)
        ->and((float) $line->weight_kg)->toBe(19.625)
        ->and((float) $line->density_kg_m3)->toBe(7850.0);
});

it('LaserDeliveryNoteLine has correct fillable fields', function () {
    $line = new LaserDeliveryNoteLine;
    expect($line->getFillable())->toContain(
        'laser_delivery_note_id', 'laser_order_line_id', 'material_id',
        'description', 'length_mm', 'width_mm', 'thickness_mm',
        'quantity', 'quantity_delivered', 'cut_length_mm', 'weight_kg', 'density_kg_m3'
    );
});

it('LaserDeliveryNoteLine relationships work', function () {
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);
    $orderLine = LaserOrderLine::withoutEvents(fn () => LaserOrderLine::create([
        'laser_order_id' => $delivery->laser_order_id,
        'material_id' => $material->id,
        'quantity' => 5,
    ]));

    $line = LaserDeliveryNoteLine::withoutEvents(fn () => LaserDeliveryNoteLine::create([
        'laser_delivery_note_id' => $delivery->id,
        'laser_order_line_id' => $orderLine->id,
        'material_id' => $material->id,
        'length_mm' => 100,
        'width_mm' => 50,
        'thickness_mm' => 2,
        'quantity' => 5,
        'quantity_delivered' => 5,
        'cut_length_mm' => 300,
        'weight_kg' => 1.5,
    ]));

    expect($line->deliveryNote)->not->toBeNull()
        ->and($line->deliveryNote->id)->toBe($line->laser_delivery_note_id)
        ->and($line->orderLine)->not->toBeNull()
        ->and($line->orderLine->id)->toBe($line->laser_order_line_id)
        ->and($line->material)->not->toBeNull()
        ->and($line->material->id)->toBe($line->material_id);
});

// ============================================================
// LaserOrderLine remaining_quantity with reserved_quantity
// ============================================================

it('calculates remaining quantity without reserved', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 20,
        'delivered_quantity' => 7,
        'total_ht' => 0,
    ]);

    expect($line->remaining_quantity)->toBe(13);
});

it('calculates remaining quantity with reserved', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 20,
        'delivered_quantity' => 5,
        'reserved_quantity' => 8,
        'total_ht' => 0,
    ]);

    expect($line->remaining_quantity)->toBe(7);
});

it('remaining quantity is zero when fully delivered', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'total_ht' => 0,
    ]);

    expect($line->remaining_quantity)->toBe(0);
});

it('remaining quantity is zero when fully reserved', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $material = LaserMaterial::factory()->create(['is_active' => true]);

    $line = LaserOrderLine::create([
        'laser_order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'reserved_quantity' => 10,
        'total_ht' => 0,
    ]);

    expect($line->remaining_quantity)->toBe(0);
});

// ============================================================
// LaserOrder deliveryNotes relationship
// ============================================================

it('LaserOrder has deliveryNotes relationship', function () {
    $order = LaserOrder::withoutEvents(fn () => LaserOrder::factory()->create());
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->create([
        'laser_order_id' => $order->id,
    ]));

    expect($order->deliveryNotes)->toHaveCount(1)
        ->and($order->deliveryNotes->first()->id)->toBe($delivery->id);
});

// ============================================================
// Reference generation
// ============================================================

it('generates unique delivery note references', function () {
    $service = app(LaserDeliveryNoteService::class);

    $ref1 = $service->generateReference();
    $ref2 = $service->generateReference();

    expect($ref1)->toStartWith('LBL-')
        ->and($ref2)->toStartWith('LBL-')
        ->and($ref1)->not->toBe($ref2);
});

// ============================================================
// LaserDocumentationService coverage
// ============================================================

it('generates correct delivery note filename', function () {
    $service = app(LaserDocumentationService::class);
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->make(['reference' => 'LBL-2026-0001']));

    expect($service->getDeliveryNoteFilename($delivery))->toBe('bon_de_livraison_LBL-2026-0001');
});

it('generates correct delivery note path', function () {
    $service = app(LaserDocumentationService::class);
    $delivery = LaserDeliveryNote::withoutEvents(fn () => LaserDeliveryNote::factory()->make(['reference' => 'LBL-2026-0001']));

    expect($service->getDeliveryNotePath($delivery))->toBe('documents/laser/delivery_notes/bon_de_livraison_LBL-2026-0001.pdf');
});

// ============================================================
// Job dispatch on delivery note created
// ============================================================

it('dispatches generate delivery note document job on created', function () {
    Queue::fake();

    LaserDeliveryNote::factory()->create();

    Queue::assertPushed(GenerateLaserDocumentJob::class, function ($job) {
        return $job->namespace === 'laser_delivery_note';
    });
});

it('dispatches generate order document job on order created', function () {
    Queue::fake();

    LaserOrder::factory()->create();

    Queue::assertPushed(GenerateLaserDocumentJob::class, function ($job) {
        return $job->namespace === 'laser_order';
    });
});

it('LaserDeliveryNoteObserver constructor accepts LaserDocumentationService', function () {
    $observer = new LaserDeliveryNoteObserver(
        app(LaserDocumentationService::class)
    );

    expect($observer)->toBeInstanceOf(LaserDeliveryNoteObserver::class);
});
