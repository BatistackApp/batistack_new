<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use Tests\Feature\Modules\Core\TestModelWithoutOverrides;
use Tests\Feature\Modules\Core\TestModelWithOverrides;

it('returns null for default getSignatureDocumentUrl', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $model = new TestModelWithoutOverrides;
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    expect($model->getSignatureDocumentUrl($signature))->toBeNull();
});

it('returns null for default getSignatureDocumentPath', function () {
    $model = new TestModelWithoutOverrides;

    expect($model->getSignatureDocumentPath())->toBeNull();
});

it('returns null for default getSignatoryName', function () {
    $model = new TestModelWithoutOverrides;

    expect($model->getSignatoryName())->toBeNull();
});

it('calls onPostSignature via handlePostSignature when method exists', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $model = new TestModelWithOverrides;
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $model->handlePostSignature($signature);
});

it('returns overridden getSignatureDocumentUrl', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $model = new TestModelWithOverrides;
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    expect($model->getSignatureDocumentUrl($signature))->toBe('https://example.com/sign/test-token');
});

it('returns overridden getSignatureDocumentPath', function () {
    $model = new TestModelWithOverrides;

    expect($model->getSignatureDocumentPath())->toBe('/tmp/test.pdf');
});

it('returns overridden getSignatoryName', function () {
    $model = new TestModelWithOverrides;

    expect($model->getSignatoryName())->toBe('Test User');
});

it('stampSignatureDocument uses default flow without file', function () {
    $doc = ThirdPartyDocument::factory()->create();
    $model = new TestModelWithoutOverrides;
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => $doc->getMorphClass(),
        'signable_id' => $doc->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    expect(fn () => $model->stampSignatureDocument($signature))
        ->toThrow(RuntimeException::class, 'Le document source de la signature est introuvable ou illisible.');
});
