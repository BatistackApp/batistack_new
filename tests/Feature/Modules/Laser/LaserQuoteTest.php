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

uses(RefreshDatabase::class);

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

it('calculates surface correctly', function () {
    $line = new LaserQuoteLine([
        'length_mm' => 1000,
        'width_mm' => 500,
    ]);

    expect($line->calculateSurface())->toBe(500000.0);
});

it('calculates weight correctly with acier s235', function () {
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

    // surface = 1000 * 500 = 500000 mm2
    // weight = (500000 / 1e6) * 2 * (7850 / 1000) = 0.5 * 2 * 7.85 = 7.85 kg
    expect($line->calculateWeight())->toBe(7.85);
});

it('calculates unit price with max(poids, metre) + programming cost', function () {
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

    // poids = 7.85 kg → prix_poids = 7.85 * 1.20 = 9.42
    // mètres = 3000/1000 = 3m → prix_metre = 3 * 0.80 = 2.40
    // max(9.42, 2.40) + 50 = 59.42
    expect($line->calculateUnitPrice())->toBe(59.42);
});

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

it('calculates total_ht with discount', function () {
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

    // unit_price = 59.42
    // total = 59.42 * 10 * (1 - 0.10) = 59.42 * 10 * 0.90 = 534.78
    expect($line->calculateTotalHt())->toBe(534.78);
});

it('recalculates totals with TVA 20%', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create([
        'name' => 'Test Client',
        'type' => 'client',
    ]);

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
        ->and($quote->total_ttc)->toBe('641.74'); // 534.78 * 1.20 = 641.736 → 641.74
});

it('deletable only when draft', function () {
    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);
    expect($quote->canBeDeleted())->toBeTrue();

    $quote->update(['status' => QuoteStatus::SENT]);
    expect($quote->canBeDeleted())->toBeFalse();
});

it('sets expires_at to 30 days by default', function () {
    $client = ThirdParty::create([
        'name' => 'Test Client',
        'type' => 'client',
    ]);

    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0002',
        'status' => QuoteStatus::DRAFT,
    ]);

    expect($quote->expires_at)->not->toBeNull()
        ->and($quote->expires_at->format('Y-m-d'))->toBe(now()->addDays(30)->format('Y-m-d'));
});

// QuoteStatus enum tests
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

// LaserQuote relationships
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

    $client = ThirdParty::create([
        'name' => 'Test Client',
        'type' => 'client',
    ]);

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

it('detects expired quote', function () {
    $quote = LaserQuote::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($quote->is_expired)->toBeTrue();
});

it('detects non-expired quote', function () {
    $quote = LaserQuote::factory()->create([
        'expires_at' => now()->addDays(10),
    ]);

    expect($quote->is_expired)->toBeFalse();
});

// LaserQuoteLine discount calculation
it('calculates discount via service', function () {
    $line = new LaserQuoteLine(['quantity' => 15]);
    expect($line->calculateDiscount())->toBe(10.0);
});

// LaserQuoteService calculateLineTotal
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

// LaserDocumentationService
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
// WORKFLOW TESTS — Cycle de vie devis + lignes
// ============================================================

it('recalculates totals when a line is created', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0010',
        'status' => QuoteStatus::DRAFT,
    ]);

    expect($quote->total_ht)->toBe('0.00');

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

    $quote->refresh();

    expect($quote->total_ht)->toBe('534.78')
        ->and($quote->total_ttc)->toBe('641.74');
});

it('recalculates totals when a line is updated', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0011',
        'status' => QuoteStatus::DRAFT,
    ]);

    $line = LaserQuoteLine::create([
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

    $quote->refresh();
    expect($quote->total_ht)->toBe('534.78');

    $line->update(['quantity' => 20, 'discount_pct' => 15, 'total_ht' => 1009.14]);
    $quote->refresh();

    expect($quote->total_ht)->toBe('1009.14')
        ->and($quote->total_ttc)->toBe('1210.97');
});

it('recalculates totals when a line is deleted', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0012',
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

    $quote->refresh();
    expect($quote->total_ht)->toBe('734.78');

    $quote->lines()->first()->delete();
    $quote->refresh();

    expect($quote->total_ht)->toBe('200.00');
});

it('dispatches GenerateLaserDocumentJob when a line is created', function () {
    Queue::fake();

    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0013',
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

it('dispatches GenerateLaserDocumentJob when a line is updated', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0014',
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

it('dispatches GenerateLaserDocumentJob when a line is deleted', function () {
    $material = LaserMaterial::create([
        'name' => 'Acier S235',
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
    ]);

    $client = ThirdParty::create(['name' => 'Client Test', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-2026-0015',
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

it('snapshots material prices on line and recalculates on thickness change', function () {
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

it('calculates explicit discount parameter in calculateTotalHt', function () {
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

    $totalWithoutExplicit = $line->calculateTotalHt();
    $totalWithExplicit = $line->calculateTotalHt(10.0);

    expect($totalWithoutExplicit)->not->toBe($totalWithExplicit)
        ->and($totalWithExplicit)->toBe(534.78);
});
