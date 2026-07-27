<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Core\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
    
    // User authentifié requis pour la création de la signature (voir SignatureService)
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->signatureService = app(SignatureService::class);
});

it('can sign an intervention using SignatureService', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::TERMINEE,
        'reference' => 'INT-SIG-01',
        'flat_rate_price' => 150.00,
        'description' => 'Test de signature',
    ]);

    $base64Signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    $signature = $this->signatureService->sign(
        model: $intervention,
        signatureData: $base64Signature,
        type: SignatureType::AUTOGRAPH,
        additionalMetadata: [
            'signer_name' => 'Jean Dupont',
            'role' => 'client',
        ]
    );

    expect($signature)->not->toBeNull()
        ->and($signature->status)->toEqual(SignatureStatus::SIGNED)
        ->and($signature->signable_type)->toEqual($intervention->getMorphClass())
        ->and($signature->signable_id)->toEqual($intervention->id)
        ->and($signature->signature_data)->toEqual($base64Signature);

    // Vérifie que la relation polymorphique fonctionne dans l'autre sens
    expect($intervention->signatures)->toHaveCount(1)
        ->and($intervention->signatures->first()->id)->toEqual($signature->id);
});

it('invalidates signature if intervention is modified', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::TERMINEE,
        'reference' => 'INT-SIG-02',
        'flat_rate_price' => 150.00,
    ])->fresh(); // <-- On recharge de la DB pour que le toArray() soit identique (casts, dates) lors de la création de la signature

    $signature = $this->signatureService->sign(
        model: $intervention,
        signatureData: 'fake-data',
    );

    // Vérification initiale : La signature est valide
    expect($this->signatureService->verify($signature))->toBeTrue();

    // Modification de l'intervention (ex: on change le prix)
    $intervention->update(['flat_rate_price' => 500.00]);

    // Force reload relation to get fresh data
    $signature->load('signable');

    // La vérification doit maintenant échouer car le checksum a changé
    expect($this->signatureService->verify($signature))->toBeFalse();
});
