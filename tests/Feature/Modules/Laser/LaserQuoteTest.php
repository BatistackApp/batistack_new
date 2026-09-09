<?php

use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Models\Tiers\ThirdParty;
use App\Services\Laser\LaserDocumentationService;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ============================================================
// QUOTE REFERENCE
// ============================================================

it('generates a valid LAQ reference', function () {
    $service = app(LaserQuoteService::class);
    $reference = $service->generateReference();

    expect($reference)->toMatch('/^LAQ-\d{4}-\d{4}$/');
});

it('creates a laser quote via factory', function () {
    $quote = LaserQuote::factory()->create();

    expect($quote)->toBeInstanceOf(LaserQuote::class)
        ->and($quote->reference)->not->toBeEmpty()
        ->and($quote->status)->toBe(QuoteStatus::DRAFT);
});

// ============================================================
// LASER QUOTE LINE — Static compute methods
// ============================================================

it('calculates surface correctly', function () {
    expect(LaserQuoteLine::computeSurface(1000, 500))->toBe(500000.0);
});

it('calculates weight correctly via static method', function () {
    expect(LaserQuoteLine::computeWeight(1000, 500, 2, 7850))->toBe(7.85);
});

it('calculates unit price via static method', function () {
    expect(LaserQuoteLine::computeUnitPrice(7.85, 1.20, 3000, 0.80, 50))->toBe(59.42);
});

// ============================================================
// LASER QUOTE LINE — Instance methods
// ============================================================

it('calculates surface on instance', function () {
    $line = new LaserQuoteLine(['length_mm' => 1000, 'width_mm' => 500]);
    expect($line->calculateSurface())->toBe(500000.0);
});

it('calculates weight on instance with material', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    expect($line->calculateWeight())->toBe(7.85);
});

it('calculates weight as zero when no material', function () {
    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
    ]);

    expect($line->calculateWeight())->toBe(0.0);
});

it('calculates unit price on instance with material', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    expect($line->calculateUnitPrice())->toBe(59.42);
});

it('calculates discount on instance', function () {
    $line = new LaserQuoteLine(['quantity' => 15]);
    expect($line->calculateDiscount())->toBe(10.0);
});

it('calculates total_ht on instance with discount', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'discount_pct' => 10,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    expect($line->calculateTotalHt())->toBe(534.78);
});

it('calculates total_ht with explicit discount override', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'discount_pct' => 0,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    expect($line->calculateTotalHt())->toBe(594.20)
        ->and($line->calculateTotalHt(10.0))->toBe(534.78);
});

it('recalculates line fields via recalculate()', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0050',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 0,
    ]);

    $line->recalculate();

    expect($line->surface_mm2)->toBe('500000.0000')
        ->and($line->weight_kg)->toBe('7.8500')
        ->and($line->unit_price_ht)->toBe('59.4200')
        ->and($line->discount_pct)->toBe('10.00')
        ->and($line->total_ht)->toBe('534.78');
});

// ============================================================
// LASER QUOTE LINE — Relationships
// ============================================================

it('line belongs to a quote', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0051',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    expect($line->quote)->not->toBeNull()
        ->and($line->quote->id)->toBe($quote->id);
});

it('line belongs to a material', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0052',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    expect($line->material)->not->toBeNull()
        ->and($line->material->id)->toBe($material->id);
});

// ============================================================
// DISCOUNT PROGRESSIVE
// ============================================================

it('applies progressive discount based on quantity', function () {
    $service = app(LaserQuoteService::class);

    expect($service->applyDiscount(1))->toBe(0.0)
        ->and($service->applyDiscount(4))->toBe(0.0)
        ->and($service->applyDiscount(5))->toBe(5.0)
        ->and($service->applyDiscount(9))->toBe(5.0)
        ->and($service->applyDiscount(10))->toBe(10.0)
        ->and($service->applyDiscount(19))->toBe(10.0)
        ->and($service->applyDiscount(20))->toBe(15.0)
        ->and($service->applyDiscount(100))->toBe(15.0);
});

// ============================================================
// LASER QUOTE SERVICE
// ============================================================

it('calculates line total via service', function () {
    $service = app(LaserQuoteService::class);
    $total = $service->calculateLineTotal(
        weightKg: 7.85,
        pricePerKg: 1.20,
        cutLengthMm: 3000,
        pricePerMeter: 0.80,
        programmingCost: 50,
        quantity: 10,
        discountPct: 10,
    );

    expect($total)->toBe(534.78);
});

it('calculateLine delegates to recalculate on model', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0053',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 0,
    ]);

    $service = app(LaserQuoteService::class);
    $service->calculateLine($line);

    $line->refresh();

    expect($line->total_ht)->toBe('534.78');
});

// ============================================================
// LASER QUOTE — RecalculatesLaserTotals trait
// ============================================================

it('recalculates totals with TVA 20%', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Test Client', 'type' => 'client']);

    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0001',
        'status' => QuoteStatus::DRAFT,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'unit_price_ht' => 59.42,
        'discount_pct' => 10,
        'total_ht' => 534.78,
    ]);

    $quote->recalculateTotals();

    expect($quote->total_ht)->toBe('534.78')
        ->and($quote->total_ttc)->toBe('641.74');
});

it('recalculates totals to zero when no lines', function () {
    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0060',
        'status' => QuoteStatus::DRAFT,
        'total_ht' => '100.00',
        'total_ttc' => '120.00',
    ]);

    $quote->recalculateTotals();

    expect($quote->fresh()->total_ht)->toBe('0.00')
        ->and($quote->fresh()->total_ttc)->toBe('0.00');
});

// ============================================================
// LASER QUOTE — Relationships & attributes
// ============================================================

it('belongs to a client', function () {
    $quote = LaserQuote::factory()->create();
    expect($quote->client)->not->toBeNull()
        ->and($quote->client)->toBeInstanceOf(ThirdParty::class);
});

it('has many lines', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Test Client', 'type' => 'client']);

    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0003',
        'status' => QuoteStatus::DRAFT,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    expect($quote->lines)->toHaveCount(1);
});

it('deletable only when draft', function () {
    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);
    expect($quote->canBeDeleted())->toBeTrue();

    $quote->update(['status' => QuoteStatus::SENT]);
    expect($quote->canBeDeleted())->toBeFalse();
});

it('detects expired quote', function () {
    $quote = LaserQuote::factory()->create(['expires_at' => now()->subDay()]);
    expect($quote->is_expired)->toBeTrue();
});

it('detects non-expired quote', function () {
    $quote = LaserQuote::factory()->create(['expires_at' => now()->addDays(10)]);
    expect($quote->is_expired)->toBeFalse();
});

it('is_expired returns false when no expires_at', function () {
    $quote = LaserQuote::factory()->make(['expires_at' => null]);
    expect($quote->is_expired)->toBeFalse();
});

// ============================================================
// QUOTE STATUS ENUM
// ============================================================

it('has correct labels for all status', function () {
    expect(QuoteStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(QuoteStatus::SENT->getLabel())->toBe('Envoyé')
        ->and(QuoteStatus::ACCEPTED->getLabel())->toBe('Accepté')
        ->and(QuoteStatus::REJECTED->getLabel())->toBe('Refusé')
        ->and(QuoteStatus::CANCELLED->getLabel())->toBe('Annulé');
});

it('has correct colors for all status', function () {
    expect(QuoteStatus::DRAFT->getColor())->toBe('gray')
        ->and(QuoteStatus::SENT->getColor())->toBe('warning')
        ->and(QuoteStatus::ACCEPTED->getColor())->toBe('success')
        ->and(QuoteStatus::REJECTED->getColor())->toBe('danger')
        ->and(QuoteStatus::CANCELLED->getColor())->toBe('gray');
});

it('has correct icons for all status', function () {
    expect(QuoteStatus::DRAFT->getIcon())->not->toBeNull()
        ->and(QuoteStatus::SENT->getIcon())->not->toBeNull()
        ->and(QuoteStatus::ACCEPTED->getIcon())->not->toBeNull()
        ->and(QuoteStatus::REJECTED->getIcon())->not->toBeNull()
        ->and(QuoteStatus::CANCELLED->getIcon())->not->toBeNull();
});

// ============================================================
// LASER DOCUMENTATION SERVICE
// ============================================================

it('generates correct quote filename', function () {
    $service = app(LaserDocumentationService::class);
    $quote = LaserQuote::factory()->make(['reference' => 'LAQ-2026-0099']);

    expect($service->getQuoteFilename($quote))->toBe('devis_laser_LAQ-2026-0099');
});

it('generates correct quote path', function () {
    $service = app(LaserDocumentationService::class);
    $quote = LaserQuote::factory()->make(['reference' => 'LAQ-2026-0099']);

    expect($service->getQuotePath($quote))->toBe('laser/quotes/devis_laser_LAQ-2026-0099.pdf');
});

// ============================================================
// LASER QUOTE OBSERVER — creating / created / updated / deleted
// ============================================================

it('sets expires_at to 30 days by default on creating', function () {
    Queue::fake();

    $client = ThirdParty::create(['name' => 'Test Client', 'type' => 'client']);

    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0002',
        'status' => QuoteStatus::DRAFT,
    ]);

    expect($quote->expires_at)->not->toBeNull()
        ->and($quote->expires_at->format('Y-m-d'))->toBe(now()->addDays(30)->format('Y-m-d'));
});

it('preserves manually set expires_at', function () {
    Queue::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $customDate = now()->addDays(60);

    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0061',
        'status' => QuoteStatus::DRAFT,
        'expires_at' => $customDate,
    ]);

    expect($quote->expires_at->format('Y-m-d'))->toBe($customDate->format('Y-m-d'));
});

it('dispatches job on quote created', function () {
    Queue::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);

    LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0062',
        'status' => QuoteStatus::DRAFT,
    ]);

    Queue::assertDispatched(GenerateLaserDocumentJob::class);
});

it('dispatches job on status change', function () {
    Queue::fake();

    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);

    Queue::fake();

    $quote->update(['status' => QuoteStatus::SENT]);

    Queue::assertDispatched(GenerateLaserDocumentJob::class);
});

it('does not dispatch job on non-status update', function () {
    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);

    Queue::fake();

    $quote->update(['terms' => 'Nouvelles conditions']);

    Queue::assertNotDispatched(GenerateLaserDocumentJob::class);
});

it('deletes PDF on quote deleted', function () {
    Queue::fake();

    Storage::fake('local');

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0063',
        'status' => QuoteStatus::DRAFT,
    ]);

    $service = app(LaserDocumentationService::class);
    $path = $service->getQuotePath($quote);
    Storage::disk('local')->put($path, 'fake-pdf');

    $quote->delete();

    Storage::disk('local')->assertMissing($path);
});

// ============================================================
// LASER QUOTE LINE OBSERVER — created / updated / deleted
// ============================================================

it('refreshes quote totals when line is created via observer', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0070',
        'status' => QuoteStatus::DRAFT,
    ]);

    expect($quote->fresh()->total_ht)->toBe('0.00');

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 10,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'unit_price_ht' => 59.42,
        'discount_pct' => 10,
        'total_ht' => 534.78,
    ]);

    expect($quote->fresh()->total_ht)->toBe('534.78')
        ->and($quote->fresh()->total_ttc)->toBe('641.74');
});

it('refreshes quote totals when line is updated via observer', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0071',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 534.78,
    ]);

    expect($quote->fresh()->total_ht)->toBe('534.78');

    $line->update(['total_ht' => 1000.00]);

    expect($quote->fresh()->total_ht)->toBe('1000.00')
        ->and($quote->fresh()->total_ttc)->toBe('1200.00');
});

it('refreshes quote totals when line is deleted via observer', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0072',
        'status' => QuoteStatus::DRAFT,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 534.78,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 2000,
        'width_mm' => 1000,
        'thickness_mm' => 3,
        'quantity' => 5,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 200.00,
    ]);

    expect($quote->fresh()->total_ht)->toBe('734.78');

    $quote->lines()->first()->delete();

    expect($quote->fresh()->total_ht)->toBe('200.00');
});

it('dispatches job when line is created via observer', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0073',
        'status' => QuoteStatus::DRAFT,
    ]);

    Queue::fake();

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    Queue::assertDispatched(GenerateLaserDocumentJob::class);
});

it('dispatches job when line is updated via observer', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0074',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    Queue::fake();

    $line->update(['quantity' => 5, 'total_ht' => 500]);

    Queue::assertDispatched(GenerateLaserDocumentJob::class);
});

it('dispatches job when line is deleted via observer', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0075',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    Queue::fake();

    $line->delete();

    Queue::assertDispatched(GenerateLaserDocumentJob::class);
});

// ============================================================
// GENERATE LASER DOCUMENT JOB
// ============================================================

it('job implements shouldQueue', function () {
    $job = new GenerateLaserDocumentJob('laser_quote', LaserQuote::factory()->make());

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('job throws on invalid namespace', function () {
    $quote = LaserQuote::factory()->create();

    $job = new GenerateLaserDocumentJob('invalid_namespace', $quote);

    $job->handle();
})->throws(\InvalidArgumentException::class, 'Invalid namespace: invalid_namespace');

// ============================================================
// MATERIAL SNAPSHOTS & THICKNESS RECALC
// ============================================================

it('changes thickness affects weight and unit price', function () {
    $material = LaserMaterial::create([
        'name' => 'Inox 304',
        'density_kg_m3' => 7900.00,
        'price_per_kg' => 3.5000,
        'price_per_meter' => 2.0000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 15.00,
    ]);

    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'cut_length_mm' => 0,
        'programming_cost' => 0,
        'price_per_kg' => 3.5000,
        'price_per_meter' => 2.0000,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    $unitPrice2mm = $line->calculateUnitPrice();

    $line->thickness_mm = 5;
    $unitPrice5mm = $line->calculateUnitPrice();

    expect($unitPrice5mm)->toBeGreaterThan($unitPrice2mm);
});

it('snapshots material prices on line create', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Inox 304',
        'density_kg_m3' => 7900.00,
        'price_per_kg' => 3.5000,
        'price_per_meter' => 2.0000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 15.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0076',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => $material->price_per_kg,
        'price_per_meter' => $material->price_per_meter,
        'total_ht' => 100,
    ]);

    $material->update(['price_per_kg' => 5.00]);

    expect($line->price_per_kg)->toBe('3.5000')
        ->and($line->fresh()->price_per_kg)->toBe('3.5000');
});

// ============================================================
// GENERATE QUOTE PDF — Service coverage
// ============================================================

it('generateQuotePdf loads relations and returns pdf path', function () {
    Queue::fake();
    Storage::fake('local');

    $company = \App\Models\Core\Company::create([
        'legal_name' => 'Test Company',
        'address' => '123 Rue Test',
        'city' => 'Paris',
        'zip_code' => '75001',
        'phone' => '0102030405',
        'email' => 'test@company.com',
        'siret' => '12345678901234',
    ]);

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0080',
        'status' => QuoteStatus::DRAFT,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    $service = mock(LaserDocumentationService::class)->makePartial();
    $service->shouldReceive('generate')->once()->andReturn('laser/quotes/devis_laser_LAQ-2026-0080.pdf');

    $result = $service->generateQuotePdf($quote);

    expect($result)->toBe('laser/quotes/devis_laser_LAQ-2026-0080.pdf');
    expect($quote->relationLoaded('client'))->toBeTrue()
        ->and($quote->relationLoaded('lines'))->toBeTrue();
});

// ============================================================
// OBSERVER INTEGRITY — recalculate() called before recalculateTotals()
// ============================================================

it('observer recalculates line total_ht when created with zero', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0090',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 0,
    ]);

    $line->refresh();

    expect($line->total_ht)->not->toBe('0.00')
        ->and($line->total_ht)->toBe('534.78');

    $quote->refresh();

    expect($quote->total_ht)->toBe('534.78')
        ->and($quote->total_ttc)->toBe('641.74');
});

it('observer recalculates line total_ht when updated with raw values', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0091',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'cut_length_mm' => 3000,
        'programming_cost' => 50,
        'quantity' => 10,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 0,
    ]);

    expect($quote->fresh()->total_ht)->toBe('534.78');

    $line->update(['quantity' => 20, 'total_ht' => 0]);

    $line->refresh();

    expect($line->total_ht)->not->toBe('0.00');

    $quote->refresh();

    expect((float) $quote->total_ht)->toBeGreaterThan(534.78);
});

it('rapid modifications always produce consistent quote totals', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0092',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 1,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 100,
    ]);

    $line->update(['quantity' => 5, 'total_ht' => 0]);
    $line->update(['quantity' => 10, 'total_ht' => 0]);
    $line->update(['quantity' => 20, 'total_ht' => 0]);

    $quote->refresh();
    $line->refresh();

    expect((float) $line->total_ht)->toBeGreaterThan(0);
    expect((float) $quote->total_ht)->toBeGreaterThan(0);
    expect((float) $quote->total_ttc)->toBeGreaterThan(0);
});
