<?php

use App\Enums\Tiers\ThirdPartyType;
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
