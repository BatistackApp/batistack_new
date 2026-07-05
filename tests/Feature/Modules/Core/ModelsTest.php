<?php

use App\Models\Core\Company;
use App\Models\Core\Setting;
use App\Models\Core\Signature;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a company using factory', function () {
    $company = Company::factory()->create();
    expect($company->id)->not->toBeNull();
});

it('can create a setting using factory', function () {
    $setting = Setting::factory()->create();
    expect($setting->id)->not->toBeNull();
});

it('can create a signature using factory and test relation', function () {
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
        'metadata' => ['user_agent' => 'Test'],
    ]);

    expect($signature->id)->not->toBeNull()
        ->and($signature->signable)->toBeInstanceOf(ThirdPartyDocument::class)
        ->and($signature->signable->id)->toBe($document->id);
});

it('can create a unit using factory', function () {
    $unit = Unit::factory()->create();
    expect($unit->id)->not->toBeNull();
});

it('can create a vat rate using factory', function () {
    $vatRate = VatRate::factory()->create();
    expect($vatRate->id)->not->toBeNull();
});
