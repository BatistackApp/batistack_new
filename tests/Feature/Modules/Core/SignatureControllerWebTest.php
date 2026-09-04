<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Core\SignatureService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    Notification::fake();
});

it('shows signature page for multi-signer token', function () {
    $quote = CustomerQuote::factory()->create();
    $service = app(SignatureService::class);
    $signature = $service->requestMultiSignature(
        $quote,
        SignatureType::AUTOGRAPH,
        [['name' => 'Alice', 'email' => 'alice@test.com']]
    );

    $signer = $signature->signers()->first();
    $response = $this->get(route('signature.show', $signer->token));

    $response->assertOk();
});

it('shows completed page for already signed signer', function () {
    $quote = CustomerQuote::factory()->create();
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signer = SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::SIGNED,
        'token' => Str::uuid()->toString(),
    ]);

    $response = $this->get(route('signature.show', $signer->token));

    $response->assertOk();
});

it('shows legacy signature page for pending signature', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $token = Str::uuid()->toString();
    $signature = Signature::create([
        'token' => $token,
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $response = $this->get(route('signature.show', $token));

    $response->assertOk();
});

it('shows completed page for legacy signed signature', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $token = Str::uuid()->toString();
    Signature::create([
        'token' => $token,
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $response = $this->get(route('signature.show', $token));

    $response->assertOk();
});

it('processes legacy signature', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $token = Str::uuid()->toString();
    Signature::create([
        'token' => $token,
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $response = $this->post(route('signature.sign', $token), [
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
    ]);

    $response->assertRedirect();
});

it('refuses as signer', function () {
    $quote = CustomerQuote::factory()->create();
    $service = app(SignatureService::class);
    $signature = $service->requestMultiSignature(
        $quote,
        SignatureType::AUTOGRAPH,
        [['name' => 'Alice', 'email' => 'alice@test.com']]
    );

    $signer = $signature->signers()->first();

    $response = $this->post(route('signature.refuse', $signer->token), [
        'reason' => 'Je refuse',
    ]);

    $response->assertRedirect();
    $signer->refresh();
    expect($signer->status)->toBe(SignatureStatus::REFUSED);
});
