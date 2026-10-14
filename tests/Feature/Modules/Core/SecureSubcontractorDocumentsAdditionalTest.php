<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('supports dry-run without moving files or changing document metadata', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $sourcePath = 'legacy/dry-run.pdf';
    $targetPath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/dry-run.pdf';
    Storage::disk('public')->put($sourcePath, '%PDF-dry-run');

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'public',
        'file_path' => $sourcePath,
    ]);

    expect(Artisan::call('subcontractors:secure-documents', ['--dry-run' => true]))->toBe(0)
        ->and($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe($sourcePath);

    Storage::disk('public')->assertExists($sourcePath);
    Storage::disk('local')->assertMissing($targetPath);
});

it('reuses an identical private copy after an interrupted migration', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $sourcePath = 'legacy/retry.pdf';
    $targetPath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/retry.pdf';
    $contents = '%PDF-identical-copy';

    Storage::disk('public')->put($sourcePath, $contents);
    Storage::disk('local')->put($targetPath, $contents);

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'public',
        'file_path' => $sourcePath,
    ]);

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(0)
        ->and($document->fresh()->file_disk)->toBe('local')
        ->and($document->fresh()->file_path)->toBe($targetPath);

    Storage::disk('local')->assertExists($targetPath);
    Storage::disk('public')->assertMissing($sourcePath);
});

it('leaves document metadata untouched when the source file is missing', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'public',
        'file_path' => 'legacy/missing.pdf',
    ]);

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(1)
        ->and($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe('legacy/missing.pdf');
});

it('migrates generated invoices linked to a subcontractor customer', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $invoice = CustomerInvoice::factory()->create(['client_id' => $company->id]);
    $sourcePath = 'legacy/customer-invoice.pdf';
    $targetPath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/customer-invoice.pdf';
    $contents = '%PDF-customer-invoice';
    Storage::disk('public')->put($sourcePath, $contents);

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => CustomerInvoice::class,
        'entity_id' => $invoice->id,
        'file_disk' => 'public',
        'file_path' => $sourcePath,
    ]);

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(0)
        ->and($document->fresh()->file_disk)->toBe('local')
        ->and($document->fresh()->file_path)->toBe($targetPath);

    Storage::disk('local')->assertExists($targetPath);
    expect(Storage::disk('local')->get($targetPath))->toBe($contents);
    Storage::disk('public')->assertMissing($sourcePath);
});

it('preserves the public source and metadata when the copied file fails SHA-256 verification', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $sourcePath = 'legacy/corrupted-copy.pdf';
    $targetPath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/corrupted-copy.pdf';
    Storage::disk('public')->put($sourcePath, '%PDF-original-content');

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'public',
        'file_path' => $sourcePath,
    ]);

    $sourceDisk = Storage::disk('public');
    $corruptedStream = fopen('php://temp', 'r+');
    fwrite($corruptedStream, '%PDF-corrupted-content');
    rewind($corruptedStream);

    $targetDisk = Mockery::mock();
    $targetDisk->shouldReceive('exists')->once()->with($targetPath)->andReturnFalse();
    $targetDisk->shouldReceive('writeStream')->once()->with($targetPath, Mockery::type('resource'))->andReturnTrue();
    $targetDisk->shouldReceive('readStream')->once()->with($targetPath)->andReturn($corruptedStream);
    $targetDisk->shouldReceive('delete')->once()->with($targetPath)->andReturnTrue();

    Storage::shouldReceive('disk')->with('public')->andReturn($sourceDisk);
    Storage::shouldReceive('disk')->with('local')->andReturn($targetDisk);

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(1)
        ->and($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe($sourcePath);

    expect($sourceDisk->get($sourcePath))->toBe('%PDF-original-content');
});
