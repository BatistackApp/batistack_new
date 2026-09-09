<?php

use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserQuoteLineObserver;
use App\Observers\Laser\LaserQuoteObserver;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ============================================================
// LaserQuoteLineObserver — direct unit tests
// ============================================================

it('line observer created calls refreshQuote', function () {
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
        'reference' => 'LAQ-TEST-OBS-001',
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
        'total_ht' => 0,
    ]);

    $quote->refresh();

    expect($quote->total_ht)->not->toBe('0.00')
        ->and($quote->total_ht)->toBe('534.78')
        ->and($quote->total_ttc)->toBe('641.74');
});

it('line observer updated recalculates quote totals', function () {
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
        'reference' => 'LAQ-TEST-OBS-002',
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

    $before = (float) $quote->fresh()->total_ht;
    expect($before)->toBeGreaterThan(0);

    $line->update(['quantity' => 20, 'total_ht' => 0]);

    $after = (float) $quote->fresh()->total_ht;
    expect($after)->toBeGreaterThan($before);
});

it('line observer deleted recalculates quote totals', function () {
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
        'reference' => 'LAQ-TEST-OBS-003',
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

    expect($quote->fresh()->total_ht)->toBe('200.00')
        ->and($quote->fresh()->total_ttc)->toBe('240.00');
});

it('line observer dispatches job on created', function () {
    Bus::fake();

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
        'reference' => 'LAQ-TEST-OBS-004',
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

    Bus::assertDispatched(GenerateLaserDocumentJob::class);
});

it('line observer dispatches job on updated', function () {
    Bus::fake();

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
        'reference' => 'LAQ-TEST-OBS-005',
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

    $line->update(['quantity' => 5]);

    Bus::assertDispatched(GenerateLaserDocumentJob::class);
});

it('line observer dispatches job on deleted', function () {
    Bus::fake();

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
        'reference' => 'LAQ-TEST-OBS-006',
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

    $line->delete();

    Bus::assertDispatched(GenerateLaserDocumentJob::class);
});

// ============================================================
// LaserQuoteObserver — direct unit tests
// ============================================================

it('quote observer creating sets expires_at default', function () {
    Bus::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-QOBS-001',
        'status' => QuoteStatus::DRAFT,
    ]);

    expect($quote->expires_at)->not->toBeNull()
        ->and($quote->expires_at->format('Y-m-d'))->toBe(now()->addDays(30)->format('Y-m-d'));
});

it('quote observer creating preserves custom expires_at', function () {
    Bus::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $custom = now()->addDays(60);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-QOBS-002',
        'status' => QuoteStatus::DRAFT,
        'expires_at' => $custom,
    ]);

    expect($quote->expires_at->format('Y-m-d'))->toBe($custom->format('Y-m-d'));
});

it('quote observer created dispatches job', function () {
    Bus::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);

    LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-QOBS-003',
        'status' => QuoteStatus::DRAFT,
    ]);

    Bus::assertDispatched(GenerateLaserDocumentJob::class);
});

it('quote observer updated dispatches job on status change', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);

    $quote->update(['status' => QuoteStatus::SENT]);

    Bus::assertDispatched(GenerateLaserDocumentJob::class);
});

it('quote observer updated does not dispatch job on non-status change', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);

    Bus::fake();

    $quote->update(['terms' => 'New terms']);

    Bus::assertNotDispatched(GenerateLaserDocumentJob::class);
});

it('quote observer deleted removes PDF file', function () {
    Bus::fake();
    Storage::fake('local');

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-QOBS-004',
        'status' => QuoteStatus::DRAFT,
    ]);

    $service = app(LaserDocumentationService::class);
    $path = $service->getQuotePath($quote);
    Storage::disk('local')->put($path, 'fake-pdf');

    $quote->delete();

    Storage::disk('local')->assertMissing($path);
});

// ============================================================
// GenerateLaserDocumentJob — handleQuote with version check
// ============================================================

it('job handleQuote generates PDF when quote unchanged', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create();

    $service = mock(LaserDocumentationService::class);
    $service->shouldReceive('generateQuotePdf')->once()->andReturn('path/to/pdf.pdf');
    app()->instance(LaserDocumentationService::class, $service);

    $job = new GenerateLaserDocumentJob('laser_quote', $quote, $quote->updated_at);
    $job->handle();
});

it('job handleQuote re-dispatches when quote was modified', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create();
    $staleTime = $quote->updated_at->copy()->subHour();

    $quote->update(['terms' => 'modified']);

    $job = new GenerateLaserDocumentJob('laser_quote', $quote, $staleTime);
    $job->handle();

    Bus::assertDispatched(GenerateLaserDocumentJob::class, function ($dispatched) use ($quote) {
        return $dispatched->model->getKey() === $quote->getKey();
    });
});

it('job constructor captures expectedUpdatedAt from model', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create();

    $job = new GenerateLaserDocumentJob('laser_quote', $quote);

    expect($job->expectedUpdatedAt)->not->toBeNull()
        ->and($job->expectedUpdatedAt->timestamp)->toBe($quote->updated_at->timestamp);
});

it('job constructor accepts explicit expectedUpdatedAt', function () {
    Bus::fake();

    $quote = LaserQuote::factory()->create();
    $custom = now()->subHour();

    $job = new GenerateLaserDocumentJob('laser_quote', $quote, $custom);

    expect($job->expectedUpdatedAt->timestamp)->toBe($custom->timestamp);
});

// ============================================================
// RecalculatesLaserTotals — trait unit tests
// ============================================================

it('recalculateTotals sums all line totals_ht', function () {
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
        'reference' => 'LAQ-TEST-TRAIT-001',
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

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => $material->id,
        'length_mm' => 2000,
        'width_mm' => 1000,
        'thickness_mm' => 3,
        'quantity' => 2,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'total_ht' => 250,
    ]);

    $quote->recalculateTotals();

    expect($quote->fresh()->total_ht)->toBe('350.00')
        ->and($quote->fresh()->total_ttc)->toBe('420.00');
});

it('recalculateTotals with no lines sets zeros', function () {
    Queue::fake();

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-TRAIT-002',
        'status' => QuoteStatus::DRAFT,
        'total_ht' => '500.00',
        'total_ttc' => '600.00',
    ]);

    $quote->recalculateTotals();

    expect($quote->fresh()->total_ht)->toBe('0.00')
        ->and($quote->fresh()->total_ttc)->toBe('0.00');
});

it('recalculateTotals applies configurable VAT rate', function () {
    config(['laser.vat_rate' => 10]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-TRAIT-003',
        'status' => QuoteStatus::DRAFT,
    ]);

    LaserQuoteLine::create([
        'laser_quote_id' => $quote->id,
        'material_id' => LaserMaterial::create([
            'name' => 'Test',
            'density_kg_m3' => 7850.00,
            'price_per_kg' => 1.0,
            'price_per_meter' => 1.0,
            'min_thickness_mm' => 0.5,
            'max_thickness_mm' => 25.0,
        ])->id,
        'length_mm' => 100,
        'width_mm' => 100,
        'thickness_mm' => 1,
        'quantity' => 1,
        'price_per_kg' => 1.0,
        'price_per_meter' => 1.0,
        'total_ht' => 1000,
    ]);

    $quote->recalculateTotals();

    expect($quote->fresh()->total_ht)->toBe('1000.00')
        ->and($quote->fresh()->total_ttc)->toBe('1100.00');

    config(['laser.vat_rate' => 20]);
});

// ============================================================
// LaserDocumentationService — generateQuotePdf
// ============================================================

it('generateQuotePdf loads relations and delegates to generate', function () {
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
        'reference' => 'LAQ-TEST-DOC-001',
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
    $service->shouldReceive('generate')->once()->andReturn('laser/quotes/test.pdf');

    $result = $service->generateQuotePdf($quote);

    expect($result)->toBe('laser/quotes/test.pdf')
        ->and($quote->relationLoaded('client'))->toBeTrue()
        ->and($quote->relationLoaded('lines'))->toBeTrue();
});

it('generateQuotePdf builds correct data array', function () {
    $company = \App\Models\Core\Company::create([
        'legal_name' => 'Test Company',
        'address' => '123 Rue Test',
        'city' => 'Paris',
        'zip_code' => '75001',
        'phone' => '0102030405',
        'email' => 'test@company.com',
        'siret' => '12345678901234',
    ]);

    $client = ThirdParty::create(['name' => 'Client', 'type' => 'client']);
    $quote = LaserQuote::create([
        'client_id' => $client->id,
        'reference' => 'LAQ-TEST-DOC-002',
        'status' => QuoteStatus::DRAFT,
    ]);

    $service = mock(LaserDocumentationService::class)->makePartial();
    $service->shouldReceive('generate')->once()->andReturnUsing(
        function ($view, $data) {
            expect($view)->toBe('pdf.laser.quote')
                ->and($data['company'])->not->toBeNull()
                ->and($data['quote'])->toBeInstanceOf(LaserQuote::class)
                ->and($data['title'])->toContain('LAQ-TEST-DOC-002')
                ->and($data['generated_at'])->not->toBeNull();

            return 'ok';
        }
    );

    $service->generateQuotePdf($quote);
});
