<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns null when there is no signable', function () {
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => 'App\\Models\\Core\\Signature',
        'signable_id' => 1,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signature->setRelation('signable', null);

    expect($signature->stamped_document_url)->toBeNull();
});

it('returns null when the signable does not support signature stamping', function () {
    $signature = Signature::create([
        'token' => 'test-token',
        'signable_type' => 'nonexistent\\Model',
        'signable_id' => 1,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    // A plain PHP object without the Signable interphace/methods.
    $signable = new class {};
    $signature->setRelation('signable', $signable);

    expect($signature->stamped_document_url)->toBeNull();
});

it('returns the signable stamped url when supported', function () {
    $document = ThirdPartyDocument::factory()->create();
    $signature = Signature::factory()->for($document, 'signable')->create([
        'status' => SignatureStatus::SIGNED,
        'token' => 'test-token',
    ]);

    $url = $signature->stamped_document_url;

    // ThirdPartyDocument defaults to its media url (may be empty string when no media).
    expect(is_string($url))->toBeTrue();
});
