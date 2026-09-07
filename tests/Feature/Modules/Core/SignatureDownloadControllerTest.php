<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function downloadTestDocument(): ThirdPartyDocument
{
    return ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);
}

it('downloads the signed pdf for a signed signature (authenticated admin)', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $document = downloadTestDocument();
    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::SIGNED,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
        'signed_at' => now(),
    ]);

    // Crée un media signé sur le document.
    $stampedPdf = sys_get_temp_dir().'/signed_'.Str::uuid().'.pdf';
    file_put_contents($stampedPdf, '%PDF-1.4 signed content');
    $document->addMedia($stampedPdf)->toMediaCollection('signed_documents');

    $this->actingAs($admin)
        ->get(route('signatures.download', $signature))
        ->assertOk()
        ->assertDownload();

    @unlink($stampedPdf);
});

it('forbids download for a non-signed signature', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $document = downloadTestDocument();
    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $this->actingAs($admin)
        ->get(route('signatures.download', $signature))
        ->assertStatus(403);
});

it('requires authentication for the download route', function () {
    $document = downloadTestDocument();
    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::SIGNED,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
        'signed_at' => now(),
    ]);

    $this->get(route('signatures.download', $signature))
        ->assertRedirect();
});