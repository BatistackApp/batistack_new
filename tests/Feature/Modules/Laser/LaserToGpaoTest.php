<?php

use App\Enums\Articles\ItemType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Jobs\Laser\GenerateLaserManufacturingOrdersJob;
use App\Models\Articles\Item;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Support\Facades\Bus;

it('valider une commande Laser crée un OF PLANNED', function () {
    Bus::fake([GenerateLaserDocumentJob::class, GenerateLaserManufacturingOrdersJob::class]);

    $order = LaserOrder::factory()->create(['status' => OrderStatus::DRAFT]);
    LaserOrderLine::factory()->create(['laser_order_id' => $order->id]);

    $order->update(['status' => OrderStatus::CONFIRMED]);

    Bus::assertDispatched(GenerateLaserManufacturingOrdersJob::class);
});

it('conversion d\'un devis signé en commande crée un OF', function () {
    Bus::fake([GenerateLaserDocumentJob::class, GenerateLaserManufacturingOrdersJob::class]);

    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::ACCEPTED]);
    LaserQuoteLine::factory()->create(['laser_quote_id' => $quote->id]);

    $service = app(LaserQuoteService::class);
    $order = $service->convertToOrder(signedLaserQuote($quote));

    expect($order->status)->toBe(OrderStatus::CONFIRMED);

    Bus::assertDispatched(GenerateLaserManufacturingOrdersJob::class);
});

it('aucun OF dupliqué lors d\'une re-validation', function () {
    Bus::fake([GenerateLaserDocumentJob::class]);

    $order = LaserOrder::factory()->create(['status' => OrderStatus::DRAFT]);
    LaserOrderLine::factory()->create(['laser_order_id' => $order->id]);

    $order->update(['status' => OrderStatus::CONFIRMED]);
    $order->update(['status' => OrderStatus::IN_PROGRESS]);
    $order->update(['status' => OrderStatus::CONFIRMED]);

    expect(ManufacturingOrder::where('laser_order_id', $order->id)->count())->toBe(1);
});

it('l\'OF référence sa commande Laser d\'origine', function () {
    Bus::fake([GenerateLaserDocumentJob::class]);

    $order = LaserOrder::factory()->create(['status' => OrderStatus::DRAFT]);
    $line = LaserOrderLine::factory()->create(['laser_order_id' => $order->id]);

    $order->update(['status' => OrderStatus::CONFIRMED]);

    $of = ManufacturingOrder::where('laser_order_id', $order->id)->first();

    expect($of)->not->toBeNull()
        ->and($of->status)->toBe(ManufacturingStatus::PLANNED)
        ->and($of->laser_order_id)->toBe($order->id)
        ->and((float) $of->quantity_planned)->toBe((float) $line->quantity);
});

it('le flux Commerce → OF existant reste inchangé', function () {
    Bus::fake([GenerateLaserDocumentJob::class, GenerateLaserManufacturingOrdersJob::class]);

    $order = LaserOrder::factory()->create(['status' => OrderStatus::DRAFT]);
    LaserOrderLine::factory()->create(['laser_order_id' => $order->id]);

    $order->update(['status' => OrderStatus::CONFIRMED]);

    $laserOfCount = ManufacturingOrder::where('laser_order_id', $order->id)->count();

    expect($laserOfCount)->toBe(0)
        ->and(ManufacturingOrder::whereNotNull('customer_order_id')->count())->toBe(0);
});
