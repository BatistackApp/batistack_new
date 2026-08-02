<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Company;
use App\Models\Core\Signature;
use App\Models\User;
use App\Services\Core\SignatureService;

beforeEach(function () {
    // Création d'un utilisateur pour simuler l'authentification requise par le service
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(SignatureService::class);
});

/**
 * Test de la méthode sign()
 */
test('il peut enregistrer une signature finalisée pour un document', function () {
    // On utilise le modèle Company comme exemple de document signable
    $company = Company::factory()->create([
        'legal_name' => 'Batistack Innovation',
        'city' => 'Paris',
    ]);

    $signatureData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...';

    $signature = $this->service->sign(
        $company,
        $signatureData,
        SignatureType::AUTOGRAPH,
        ['location' => 'Bureau Principal']
    );

    // Vérifications de l'objet retourné
    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->status)->toBe(SignatureStatus::SIGNED)
        ->and($signature->type)->toBe(SignatureType::AUTOGRAPH)
        ->and($signature->signable_id)->toBe($company->id)
        ->and($signature->user_id)->toBe($this->user->id)
        ->and($signature->checksum)->not->toBeEmpty()
        ->and($signature->metadata['source'])->toBe('internal_erp')
        ->and($signature->metadata['location'])->toBe('Bureau Principal');

    // Vérification des métadonnées

    // Vérification en base de données
    $this->assertDatabaseHas('signatures', [
        'id' => $signature->id,
        'status' => SignatureStatus::SIGNED,
        'signable_type' => $company->getMorphClass(),
        'signable_id' => $company->id,
    ]);
});

/**
 * Test de la méthode requestSignature()
 */
test('il peut initier une demande de signature en attente', function () {
    $company = Company::factory()->create();

    $signature = $this->service->requestSignature($company, SignatureType::OTP);

    expect($signature->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->type)->toBe(SignatureType::OTP)
        ->and($signature->signature_data)->toBeNull()
        ->and($signature->signed_at)->toBeNull();

    $this->assertDatabaseHas('signatures', [
        'id' => $signature->id,
        'status' => SignatureStatus::PENDING,
    ]);
});

/**
 * Test de la méthode verify() - Échec par modification
 */
test('il invalide une signature si le document a été modifié après signature', function () {
    $company = Company::factory()->create(['legal_name' => 'Document Original']);

    $signature = $this->service->sign($company, 'fake_data');

    // Simulation d'une modification frauduleuse ou légitime du document
    $company->update(['legal_name' => 'Document Modifié']);

    // Le checksum ne doit plus correspondre
    expect($this->service->verify($signature))->toBeFalse();
});

/**
 * Test de la méthode verify() - Échec par statut
 */
test('il invalide la vérification si le statut n\'est pas SIGNED', function () {
    $company = Company::factory()->create();
    $signature = $this->service->requestSignature($company);

    // Une signature en attente ne peut pas être "vérifiée" comme intègre
    expect($this->service->verify($signature))->toBeFalse();
});

/**
 * Test de la génération de Checksum
 */
test('le checksum est unique pour des données différentes', function () {
    $company1 = Company::factory()->create(['legal_name' => 'Doc A']);
    $company2 = Company::factory()->create(['legal_name' => 'Doc B']);

    $sig1 = $this->service->sign($company1, 'data');
    $sig2 = $this->service->sign($company2, 'data');

    expect($sig1->checksum)->not->toBe($sig2->checksum);
});
