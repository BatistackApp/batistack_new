<?php

use App\\Enums\\Tiers\\ThirdPartyType;
use App\\Http\\Controllers\\Subcontractor\\PrivateDocumentController;
use App\\Models\\Commerce\\CustomerInvoice;
use App\\Models\\Commerce\\SubcontractorSituation;
use App\\Models\\Core\\GeneratedDocument;
use App\\Models\\Tiers\\Contact;
use App\\Models\\Tiers\\ThirdParty;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Storage;
use Symfony\\Component\\HttpKernel\\Exception\\HttpException;

uses(RefreshDatabase::class);

function privateDocumentPortalIdentity(): array
{
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    return [$user, $company, $request];
}

it('streams a generated subcontractor document inline and as an attachment', function () {
    Storage::fake('local');
    [, $company, $request] = privateDocumentPortalIdentity();
    $document = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'local',
        'file_path' => 'private/document.pdf',
        'file_name' => 'document',
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-private');

    $controller = app(PrivateDocumentController::class);

    $preview = $controller->previewGenerated($request, $document->id);
    expect($preview->headers->get('Content-Disposition'))->toBe('inline; filename="document.pdf"');

    $download = $controller->downloadGenerated($request, $document->id);
    expect($download->headers->get('Content-Disposition'))->toBe('attachment; filename="document.pdf"')
        ->and($download->headers->get('Cache-Control'))->toBe('private, no-store, max-age=0')
        ->and($download->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('streams generated documents for an administrator endpoint', function () {
    Storage::fake('local');
    $document = GeneratedDocument::factory()->create([
        'file_disk' => 'local',
        'file_path' => 'private/admin-document.pdf',
        'file_name' => 'admin-document',
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-private');

    $response = app(PrivateDocumentController::class)->previewGeneratedForAdmin($document->id);

    expect($response->headers->get('Content-Disposition'))->toBe('inline; filename="admin-document.pdf"');
});

it('returns not found for a missing private file and for a document owned by another subcontractor', function () {
    Storage::fake('local');
    [, $company, $request] = privateDocumentPortalIdentity();
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    $foreignDocument = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $otherCompany->id,
        'file_disk' => 'local',
        'file_path' => 'private/foreign.pdf',
    ]);

    expect(fn () => app(PrivateDocumentController::class)->previewGenerated($request, $foreignDocument->id))
        ->toThrow(HttpException::class);

    $missingFile = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'local',
        'file_path' => 'private/missing.pdf',
    ]);

    expect(fn () => app(PrivateDocumentController::class)->downloadGenerated($request, $missingFile->id))
        ->toThrow(HttpException::class);
});

it('returns not found when a situation has no invoice media', function () {
    Storage::fake('local');
    [, $company, $request] = privateDocumentPortalIdentity();
    $situation = SubcontractorSituation::factory()->create(['subcontractor_id' => $company->id]);

    expect(fn () => app(PrivateDocumentController::class)->downloadInvoice($request, $situation->id))
        ->toThrow(HttpException::class);
});

it('rejects a user without a subcontractor contact', function () {
    Storage::fake('local');
    $user = User::factory()->create(['is_tiers' => true]);
    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);
    $document = GeneratedDocument::factory()->create([
        'file_disk' => 'local',
        'file_path' => 'private/document.pdf',
    ]);

    expect(fn () => app(PrivateDocumentController::class)->downloadGenerated($request, $document->id))
        ->toThrow(HttpException::class);
});

it('streams an invoice for the owning subcontractor only', function () {
    Storage::fake('local');
    [, $company, $request] = privateDocumentPortalIdentity();
    $situation = SubcontractorSituation::factory()->create(['subcontractor_id' => $company->id]);
    $media = $situation->addMediaFromString('%PDF-invoice')
        ->usingFileName('invoice.pdf')
        ->toMediaCollection('invoice_document');

    $response = app(PrivateDocumentController::class)->downloadInvoice($request, $situation->id);

    expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="invoice.pdf"')
        ->and($media->fresh()->disk)->toBe('local');
});
