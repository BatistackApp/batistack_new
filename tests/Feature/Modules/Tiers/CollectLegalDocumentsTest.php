<?php

use App\Contracts\Tiers\LegalDocumentProviderInterface;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\CollectLegalDocumentsJob;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Notifications\Tiers\DocumentExpiredNotification;
use App\Notifications\Tiers\DocumentExpiringNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Notification::fake();
    Config::set('services.api_entreprise.token', 'test-token');
    Config::set('services.api_entreprise.base_url', 'https://api.entreprise.api.gouv.fr');
});

it('dispatches CollectLegalDocumentsJob on subcontractor creation with siren', function () {
    Queue::fake();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
    ]);

    Queue::assertPushed(CollectLegalDocumentsJob::class, function ($job) use ($thirdParty) {
        return $job->thirdParty->id === $thirdParty->id;
    });
});

it('dispatches CollectLegalDocumentsJob on client creation with siren', function () {
    Queue::fake();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'siren' => '987654321',
    ]);

    Queue::assertPushed(CollectLegalDocumentsJob::class, function ($job) use ($thirdParty) {
        return $job->thirdParty->id === $thirdParty->id;
    });
});

it('does not dispatch job for supplier type', function () {
    Queue::fake();

    ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUPPLIER,
        'siren' => '123456789',
    ]);

    Queue::assertNotPushed(CollectLegalDocumentsJob::class);
});

it('does not dispatch job without siren', function () {
    Queue::fake();

    ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => null,
    ]);

    Queue::assertNotPushed(CollectLegalDocumentsJob::class);
});

it('fetches urssaf attestation successfully', function () {
    $pdfContent = '%PDF-1.4 fake pdf content';

    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')
        ->once()
        ->with('123456789')
        ->andReturn([
            'file_content' => $pdfContent,
            'validity_start_date' => '2026-01-01',
            'validity_end_date' => '2026-12-31',
            'entity_status' => 'ok',
        ]);
    $mockProvider->shouldReceive('fetchAttestationRne')->once()->andReturn(null);

    $this->app->bind(LegalDocumentProviderInterface::class, fn () => $mockProvider);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
    ]);

    // Manually run the job
    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);

    $document = ThirdPartyDocument::where('third_party_id', $thirdParty->id)
        ->where('type', ThirdPartyDocumentType::URSSAF)
        ->first();

    expect($document)->not->toBeNull()
        ->and($document->expiration_date->format('Y-m-d'))->toBe('2026-12-31')
        ->and($document->status)->toBe(ThirdPartyDocumentStatus::VALID)
        ->and($document->hasMedia('third_party_documents'))->toBeTrue();

    $thirdParty->refresh();
    expect($thirdParty->last_legal_sync_at)->not->toBeNull();
});

it('fetches rne attestation successfully', function () {
    $pdfContent = '%PDF-1.4 fake rne pdf';

    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->once()->andReturn(null);
    $mockProvider->shouldReceive('fetchAttestationRne')
        ->once()
        ->with('123456789')
        ->andReturn([
            'file_content' => $pdfContent,
            'denomination' => 'Test SARL',
            'forme_juridique' => 'SARL',
            'date_immatriculation' => '2020-01-01',
        ]);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'siren' => '123456789',
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);

    $document = ThirdPartyDocument::where('third_party_id', $thirdParty->id)
        ->where('type', ThirdPartyDocumentType::KBIS)
        ->first();

    expect($document)->not->toBeNull()
        ->and($document->expiration_date)->toBeNull()
        ->and($document->status)->toBe(ThirdPartyDocumentStatus::VALID)
        ->and($document->hasMedia('third_party_documents'))->toBeTrue();
});

it('handles urssaf 404 gracefully', function () {
    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->once()->andReturn(null);
    $mockProvider->shouldReceive('fetchAttestationRne')->once()->andReturn(null);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);

    $urssafDoc = ThirdPartyDocument::where('third_party_id', $thirdParty->id)
        ->where('type', ThirdPartyDocumentType::URSSAF)
        ->first();
    expect($urssafDoc)->toBeNull();

    $thirdParty->refresh();
    expect($thirdParty->last_legal_sync_at)->not->toBeNull();
});

it('handles rne 404 gracefully', function () {
    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->once()->andReturn(null);
    $mockProvider->shouldReceive('fetchAttestationRne')->once()->andReturn(null);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);

    $rneDoc = ThirdPartyDocument::where('third_party_id', $thirdParty->id)
        ->where('type', ThirdPartyDocumentType::KBIS)
        ->first();
    expect($rneDoc)->toBeNull();
});

it('skips collection when siren is empty', function () {
    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->never();
    $mockProvider->shouldReceive('fetchAttestationRne')->never();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => null,
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);
});

it('skips collection for supplier type', function () {
    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->never();
    $mockProvider->shouldReceive('fetchAttestationRne')->never();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUPPLIER,
        'siren' => '123456789',
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);
});

it('dispatches expiring notification at j-30', function () {
    Notification::fake([DocumentExpiringNotification::class]);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'email' => 'test@example.com',
    ]);

    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::URSSAF,
        'status' => ThirdPartyDocumentStatus::VALID,
        'expiration_date' => now()->addDays(30),
    ]);

    Artisan::call('app:check-third-party-documents');

    Notification::assertSentTimes(DocumentExpiringNotification::class, 1);
});

it('dispatches expired notification at j-0', function () {
    Notification::fake([DocumentExpiredNotification::class]);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'email' => 'test@example.com',
    ]);

    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::URSSAF,
        'status' => ThirdPartyDocumentStatus::VALID,
        'expiration_date' => now()->subDay(),
    ]);

    Artisan::call('app:check-third-party-documents');

    Notification::assertSentTimes(DocumentExpiredNotification::class, 1);
});

it('check command marks expired documents', function () {
    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
    ]);

    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::URSSAF,
        'status' => ThirdPartyDocumentStatus::VALID,
        'expiration_date' => now()->subDays(5),
    ]);

    Artisan::call('app:check-third-party-documents');

    $document->refresh();
    expect($document->status)->toBe(ThirdPartyDocumentStatus::EXPIRED);
});

it('dispatches jobs with command for specific siren', function () {
    Queue::fake();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
    ]);

    Artisan::call('tiers:collect-legal-documents', ['--siren' => '123456789']);

    Queue::assertPushed(CollectLegalDocumentsJob::class, function ($job) use ($thirdParty) {
        return $job->thirdParty->id === $thirdParty->id;
    });
});

it('dispatches jobs with command for all active subcontractors and clients', function () {
    Queue::fake();

    ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '111111111',
        'is_active' => true,
    ]);

    ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'siren' => '222222222',
        'is_active' => true,
    ]);

    // Supplier should not be dispatched
    ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUPPLIER,
        'siren' => '333333333',
        'is_active' => true,
    ]);

    Artisan::call('tiers:collect-legal-documents', ['--all' => true]);

    // 2 from observer (SUBCONTRACTOR + CLIENT) + 2 from command = 4
    Queue::assertPushed(CollectLegalDocumentsJob::class, 4);
});

it('updates last_legal_sync_at after collection', function () {
    $mockProvider = Mockery::mock(LegalDocumentProviderInterface::class);
    $mockProvider->shouldReceive('fetchAttestationUrssaf')->once()->andReturn(null);
    $mockProvider->shouldReceive('fetchAttestationRne')->once()->andReturn(null);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'siren' => '123456789',
        'last_legal_sync_at' => null,
    ]);

    $job = new CollectLegalDocumentsJob($thirdParty);
    $job->handle($mockProvider);

    $thirdParty->refresh();
    expect($thirdParty->last_legal_sync_at)->not->toBeNull();
});
