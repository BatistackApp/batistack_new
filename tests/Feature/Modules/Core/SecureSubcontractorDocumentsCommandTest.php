<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('migrates legacy generated subcontractor documents only after SHA-256 verification', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $sourcePath = 'documents/legacy-invoice.pdf';
    $privatePath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/'.substr(hash('sha256', $sourcePath), 0, 12).'/legacy-invoice.pdf';
    $contents = '%PDF-1.4 legacy invoice bytes';

    Storage::disk('public')->put($sourcePath, $contents);

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
        ->and($document->fresh()->file_path)->toBe($privatePath);

    Storage::disk('local')->assertExists($privatePath);
    expect(Storage::disk('local')->get($privatePath))->toBe($contents);
    Storage::disk('public')->assertMissing($sourcePath);
});

it('keeps the public source and metadata unchanged when an existing private copy differs', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $sourcePath = 'documents/legacy-invoice.pdf';
    $privatePath = 'subcontractors/'.$company->id.'/documents/commerce/invoice/'.substr(hash('sha256', $sourcePath), 0, 12).'/legacy-invoice.pdf';

    Storage::disk('public')->put($sourcePath, '%PDF-1.4 original bytes');
    Storage::disk('local')->put($privatePath, '%PDF-1.4 different bytes');

    $document = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'invoice',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'public',
        'file_path' => $sourcePath,
    ]);

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(1)
        ->and($document->fresh()->file_disk)->toBe('public')
        ->and($document->fresh()->file_path)->toBe($sourcePath);

    Storage::disk('public')->assertExists($sourcePath);
    expect(Storage::disk('public')->get($sourcePath))->toBe('%PDF-1.4 original bytes');
    expect(Storage::disk('local')->get($privatePath))->toBe('%PDF-1.4 different bytes');
});

it('migrates legacy subcontractor situation media from public to private storage', function () {
    Storage::fake('public');
    Storage::fake('local');

    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $situation = SubcontractorSituation::factory()->create(['subcontractor_id' => $company->id]);
    $media = $situation->addMediaFromString('%PDF-1.4 old situation invoice')
        ->usingFileName('situation-invoice.pdf')
        ->toMediaCollection('invoice_document');

    $path = $media->getPathRelativeToRoot();
    Storage::disk('public')->put($path, '%PDF-1.4 old situation invoice');
    Storage::disk('local')->delete($path);
    $media->disk = 'public';
    $media->save();

    expect(Artisan::call('subcontractors:secure-documents'))->toBe(0)
        ->and($media->fresh()->disk)->toBe('local');

    Storage::disk('local')->assertExists($path);
    expect(Storage::disk('local')->get($path))->toBe('%PDF-1.4 old situation invoice');
    Storage::disk('public')->assertMissing($path);
});
