<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\GeneratedDocuments\GeneratedDocumentResource;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createSubcontractorPortalUser(): array
{
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    return [$user, $company];
}

it('serves generated documents only to the subcontractor company they belong to', function () {
    Storage::fake('local');
    [$user, $company] = createSubcontractorPortalUser();
    $document = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
        'file_disk' => 'local',
        'file_path' => 'documents/private/subcontractor-test.pdf',
        'file_name' => 'subcontractor-test',
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-1.4 private document');

    $response = $this->actingAs($user)->get(route('subcontractor.documents.generated.download', [
        'document' => $document->id,
    ]));

    $response->assertOk();
    $cacheControl = explode(', ', $response->headers->get('Cache-Control'));
    sort($cacheControl);

    expect($cacheControl)->toBe(['max-age=0', 'no-store', 'private']);
    $response->assertHeader('Content-Disposition', 'attachment; filename="subcontractor-test.pdf"');
});

it('returns not found when a subcontractor requests another company generated document', function () {
    Storage::fake('local');
    [$user] = createSubcontractorPortalUser();
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $document = GeneratedDocument::factory()->create([
        'entity_type' => ThirdParty::class,
        'entity_id' => $otherCompany->id,
        'file_disk' => 'local',
        'file_path' => 'documents/private/other-company.pdf',
        'file_name' => 'other-company',
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-1.4 other company');

    $this->actingAs($user)
        ->get(route('subcontractor.documents.generated.preview', ['document' => $document->id]))
        ->assertNotFound();
});

it('downloads an invoice only for the authenticated subcontractor company', function () {
    Storage::fake('local');
    [$user, $company] = createSubcontractorPortalUser();
    $situation = SubcontractorSituation::factory()->create([
        'subcontractor_id' => $company->id,
    ]);
    $situation->addMediaFromString('%PDF-1.4 private invoice')
        ->usingFileName('invoice.pdf')
        ->toMediaCollection('invoice_document');

    $this->actingAs($user)
        ->get(route('subcontractor.documents.invoice.download', ['situation' => $situation->id]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="invoice.pdf"');

    $otherSituation = SubcontractorSituation::factory()->create();
    $this->actingAs($user)
        ->get(route('subcontractor.documents.invoice.download', ['situation' => $otherSituation->id]))
        ->assertNotFound();
});
it('lists and serves customer invoices addressed to the authenticated subcontractor', function () {
    Storage::fake('local');
    [$user, $company] = createSubcontractorPortalUser();
    $invoice = CustomerInvoice::factory()->create(['client_id' => $company->id]);
    $document = GeneratedDocument::factory()->create([
        'entity_type' => CustomerInvoice::class,
        'entity_id' => $invoice->id,
        'file_disk' => 'local',
        'file_path' => 'subcontractors/'.$company->id.'/documents/commerce/invoice.pdf',
        'file_name' => $invoice->reference,
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-1.4 customer invoice');

    $this->actingAs($user);

    expect(GeneratedDocumentResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($document->id);

    $this->get(route('subcontractor.documents.generated.preview', ['document' => $document->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
it('does not serve another subcontractor customer invoice document', function () {
    Storage::fake('local');
    [$user] = createSubcontractorPortalUser();
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $invoice = CustomerInvoice::factory()->create(['client_id' => $otherCompany->id]);
    $document = GeneratedDocument::factory()->create([
        'entity_type' => CustomerInvoice::class,
        'entity_id' => $invoice->id,
        'file_disk' => 'local',
        'file_path' => 'subcontractors/'.$otherCompany->id.'/documents/commerce/other-invoice.pdf',
        'file_name' => $invoice->reference,
    ]);
    Storage::disk('local')->put($document->file_path, '%PDF-1.4 other customer invoice');

    $this->actingAs($user)
        ->get(route('subcontractor.documents.generated.download', ['document' => $document->id]))
        ->assertNotFound();
});

it('lists all generated document modules and types for the subcontractor and its invoices', function () {
    [$user, $company] = createSubcontractorPortalUser();
    $invoice = CustomerInvoice::factory()->create(['client_id' => $company->id]);

    $thirdPartyDocument = GeneratedDocument::factory()->create([
        'module' => 'rh',
        'type' => 'attestation_salaire',
        'entity_type' => ThirdParty::class,
        'entity_id' => $company->id,
    ]);
    $invoiceDocument = GeneratedDocument::factory()->create([
        'module' => 'commerce',
        'type' => 'facture',
        'entity_type' => CustomerInvoice::class,
        'entity_id' => $invoice->id,
    ]);

    $this->actingAs($user);

    expect(GeneratedDocumentResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($thirdPartyDocument->id, $invoiceDocument->id);
});


it('lists and serves private media documents attached to the subcontractor and its contract documents', function () {
    Storage::fake('local');
    [$user, $company] = createSubcontractorPortalUser();

    $contractDocument = ThirdPartyDocument::factory()->create(['third_party_id' => $company->id]);
    $media = $contractDocument->addMediaFromString('%PDF-1.4 signed subcontractor document')
        ->usingFileName('attestation.pdf')
        ->toMediaCollection('signed_documents', 'local');

    $this->actingAs($user);

    expect(PrivateMediaDocumentResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($media->id);

    $this->get(route('subcontractor.documents.media.preview', ['media' => $media->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'private, no-store, max-age=0');

    $this->get(route('subcontractor.documents.media.download', ['media' => $media->id]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="attestation.pdf"');
});

it('does not list or serve private media belonging to another subcontractor', function () {
    Storage::fake('local');
    [$user] = createSubcontractorPortalUser();
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $contractDocument = ThirdPartyDocument::factory()->create(['third_party_id' => $otherCompany->id]);
    $media = $contractDocument->addMediaFromString('%PDF-1.4 other company document')
        ->usingFileName('other.pdf')
        ->toMediaCollection('signed_documents', 'local');

    $this->actingAs($user);

    expect(PrivateMediaDocumentResource::getEloquentQuery()->pluck('id')->all())
        ->not->toContain($media->id);

    $this->get(route('subcontractor.documents.media.download', ['media' => $media->id]))
        ->assertNotFound();
});
