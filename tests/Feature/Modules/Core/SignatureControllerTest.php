<?php

use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Services\Core\PdfStamperService;

uses(RefreshDatabase::class);

it('displays the signature page', function () {
    $document = ThirdPartyDocument::create([
        'type' => \App\Enums\Tiers\ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => \App\Enums\Tiers\ThirdPartyDocumentStatus::VALID,
        'third_party_id' => \App\Models\Tiers\ThirdParty::factory()->create()->id,
    ]);

    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => \App\Models\User::factory()->create()->id,
        'type' => \App\Enums\Core\SignatureType::AUTOGRAPH,
        'status' => \App\Enums\Core\SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $response = $this->get(route('signature.show', $signature->token));

    $response->assertStatus(200);
});

it('can sign the document', function () {
    $document = ThirdPartyDocument::create([
        'type' => \App\Enums\Tiers\ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => \App\Enums\Tiers\ThirdPartyDocumentStatus::VALID,
        'third_party_id' => \App\Models\Tiers\ThirdParty::factory()->create()->id,
    ]);

    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => \App\Models\User::factory()->create()->id,
        'type' => \App\Enums\Core\SignatureType::AUTOGRAPH,
        'status' => \App\Enums\Core\SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $mockStamper = Mockery::mock(PdfStamperService::class);
    $mockStamper->shouldReceive('stamp')->andReturn(sys_get_temp_dir() . '/dummy.pdf');
    file_put_contents(sys_get_temp_dir() . '/dummy.pdf', 'dummy content');

    app()->instance(PdfStamperService::class, $mockStamper);

    $response = $this->post(route('signature.sign', $signature->token), [
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        'ip_address' => '127.0.0.1'
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Document signé avec succès !');

    $signature->refresh();
    expect($signature->status)->toBe(\App\Enums\Core\SignatureStatus::SIGNED);

    @unlink(sys_get_temp_dir() . '/dummy.pdf');
});
