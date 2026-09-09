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
// LASER QUOTE OBSERVER + LINE OBSERVER + JOB
// Covered in LaserObserverTest.php
// ============================================================

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
// DENSITY SNAPSHOT
// ============================================================

it('snapshots density on recalculate', function () {
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
        'reference' => 'LAQ-2026-0095',
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
        'total_ht' => 0,
    ]);

    $line->refresh();

    expect($line->density_kg_m3)->toBe('7850.00');
});

it('uses snapshoted density for weight calculation', function () {
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
        'density_kg_m3' => 7850.00,
        'material_id' => $material->id,
    ]);
    $line->setRelation('material', $material);

    expect($line->calculateWeight())->toBe(7.85);

    $material->update(['density_kg_m3' => 8000.00]);

    expect($line->calculateWeight())->toBe(7.85);
});

it('weight changes when material density changes and line is recalculated', function () {
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
        'reference' => 'LAQ-2026-0096',
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
        'total_ht' => 0,
    ]);

    $line->refresh();
    $weightBefore = (float) $line->weight_kg;

    $material->update(['density_kg_m3' => 8000.00]);

    $line->update(['quantity' => 2]);
    $line->refresh();

    expect((float) $line->weight_kg)->toBe($weightBefore);
});

// ============================================================
// DISCOUNT — Exclusivement automatique
// ============================================================

it('discount is always automatic based on quantity', function () {
    $service = app(LaserQuoteService::class);

    expect($service->applyDiscount(1))->toBe(0.0)
        ->and($service->applyDiscount(4))->toBe(0.0)
        ->and($service->applyDiscount(5))->toBe(5.0)
        ->and($service->applyDiscount(10))->toBe(10.0)
        ->and($service->applyDiscount(20))->toBe(15.0);
});

it('recalculate always uses automatic discount', function () {
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
        'reference' => 'LAQ-2026-0097',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 3,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'discount_pct' => 20,
        'total_ht' => 0,
    ]);

    $line->refresh();

    expect($line->discount_pct)->toBe('0.00');
});

it('quantity 5 gets automatic 5% discount via recalculate', function () {
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
        'reference' => 'LAQ-2026-0098',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 1000,
        'width_mm' => 500,
        'thickness_mm' => 2,
        'quantity' => 5,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 0,
    ]);

    $line->refresh();

    expect($line->discount_pct)->toBe('5.00');
});
