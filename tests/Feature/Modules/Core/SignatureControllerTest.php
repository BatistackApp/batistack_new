<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Core\PdfStamperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('displays the signature page', function () {
    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $response = $this->get(route('signature.show', $signature->token));

    $response->assertStatus(200);
});

it('can sign the document', function () {
    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $mockStamper = Mockery::mock(PdfStamperService::class);
    $mockStamper->shouldReceive('stamp')->andReturn(sys_get_temp_dir().'/dummy.pdf');
    file_put_contents(sys_get_temp_dir().'/dummy.pdf', 'dummy content');

    app()->instance(PdfStamperService::class, $mockStamper);

    $response = $this->post(route('signature.sign', $signature->token), [
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        'ip_address' => '127.0.0.1',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Document signé avec succès !');

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::SIGNED);

    @unlink(sys_get_temp_dir().'/dummy.pdf');
});
