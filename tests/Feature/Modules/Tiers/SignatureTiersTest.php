<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Core\SignatureService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->user = User::factory()->create();
    Auth::login($this->user);
});

it('requests a signature for a third party document', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
    ]);

    $service = app(SignatureService::class);
    $signature = $service->requestSignature($document, SignatureType::AUTOGRAPH);

    expect($signature->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signable_type)->toBe($document->getMorphClass())
        ->and($signature->signable_id)->toBe($document->id);
});

it('signs a third party document', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
    ]);

    $service = app(SignatureService::class);
    $signature = $service->sign($document, 'base64_fake_signature_data', SignatureType::AUTOGRAPH);

    expect($signature->status)->toBe(SignatureStatus::SIGNED)
        ->and($signature->signature_data)->toBe('base64_fake_signature_data');
});
