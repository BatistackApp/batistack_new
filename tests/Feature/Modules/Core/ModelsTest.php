<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Company;
use App\Models\Core\Setting;
use App\Models\Core\Signature;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
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
