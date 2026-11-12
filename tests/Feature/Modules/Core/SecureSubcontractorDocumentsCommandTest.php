<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Storage;

it('copies subcontractor generated documents to private storage and retains the public source', function () {
    Storage::fake('public');
    Storage::fake('local');

    $subcontractor = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
    ]);

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'facture',
        'entity_type' => ThirdParty::class,
        'entity_id' => $subcontractor->id,
        'file_disk' => 'public',
        'file_path' => 'documents/commerce/facture/invoice.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, '%PDF-1.7 subcontractor invoice');

    $this->artisan('subcontractors:secure-documents')
        ->assertExitCode(0);

    $privatePath = "subcontractors/{$subcontractor->id}/documents/commerce/facture/invoice.pdf";

    expect($document->fresh()->file_disk)->toBe('local')
        ->and($document->fresh()->file_path)->toBe($privatePath);

    Storage::disk('local')->assertExists($privatePath);
    Storage::disk('public')->assertExists($document->getOriginal('file_path'));
});

it('does not change document references in dry-run mode', function () {
    Storage::fake('public');
    Storage::fake('local');

    $subcontractor = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
    ]);

    $document = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $subcontractor->id,
        'file_disk' => 'public',
        'file_path' => 'documents/tiers/subcontractor.pdf',
    ]);

    Storage::disk('public')->put($document->file_path, '%PDF-1.7 source');

    $this->artisan('subcontractors:secure-documents', ['--dry-run' => true])
        ->assertExitCode(0);

    expect($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe('documents/tiers/subcontractor.pdf');

    Storage::disk('local')->assertMissing(
        "subcontractors/{$subcontractor->id}/documents/tiers/".basename($document->file_path)
    );
    Storage::disk('public')->assertExists($document->file_path);
});

it('reports a failed migration when the public source file is missing', function () {
    Storage::fake('public');
    Storage::fake('local');

    $subcontractor = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
    ]);

    $document = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $subcontractor->id,
        'file_disk' => 'public',
        'file_path' => 'documents/tiers/missing.pdf',
    ]);

    $this->artisan('subcontractors:secure-documents')
        ->assertExitCode(1);

    expect($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe('documents/tiers/missing.pdf');
});

it('uses signed temporary URLs for private document disks', function (string $diskName) {
    $disk = Mockery::mock();
    $disk->shouldReceive('temporaryUrl')
        ->once()
        ->with('private/document.pdf', Mockery::type(DateTimeInterface::class))
        ->andReturn('https://private.example.test/signed-document');

    Storage::shouldReceive('disk')
        ->once()
        ->with($diskName)
        ->andReturn($disk);

    $document = new GeneratedDocument([
        'file_disk' => $diskName,
        'file_path' => 'private/document.pdf',
    ]);

    expect($document->temporaryUrl())->toBe('https://private.example.test/signed-document');
})->with(['local', 's3', 's3-private']);
