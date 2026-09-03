<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Core\SignatureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
    Mail::fake();
    Notification::fake();
    $this->service = app(SignatureService::class);
    $this->user = User::factory()->create();
});

it('can create a signature with multiple signers', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signers = [
        ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'role' => 'Client'],
        ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'role' => 'Manager'],
        ['name' => 'Pierre Durand', 'email' => 'pierre@example.com', 'role' => 'Comptable'],
    ];

    $signature = $this->service->requestMultiSignature(
        $document,
        SignatureType::AUTOGRAPH,
        $signers,
        '/tmp/test.pdf'
    );

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signers)->toHaveCount(3)
        ->and($signature->signers->first()->name)->toBe('Jean Dupont')
        ->and($signature->signers->first()->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signers->first()->token)->not->toBeEmpty()
        ->and($signature->metadata['signers_count'])->toBe(3);
});

it('can sign as a specific signer', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = $this->service->requestMultiSignature(
        $document,
        SignatureType::AUTOGRAPH,
        [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'role' => 'Client'],
            ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'role' => 'Manager'],
        ]
    );

    $signerToken = $signature->signers->first()->token;

    $signer = $this->service->signAsSigner(
        $signerToken,
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
        '127.0.0.1',
        'TestAgent'
    );

    expect($signer->status)->toBe(SignatureStatus::SIGNED)
        ->and($signer->signed_at)->not->toBeNull()
        ->and($signer->signature->status)->toBe(SignatureStatus::PENDING); // Not all signed yet

    // Second signer signs — workflow completes
    $signer2Token = $signature->signers->last()->token;
    $this->service->signAsSigner(
        $signer2Token,
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
        '127.0.0.1',
        'TestAgent'
    );

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::SIGNED);
});

it('can refuse as a specific signer and stops workflow', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = $this->service->requestMultiSignature(
        $document,
        SignatureType::AUTOGRAPH,
        [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'role' => 'Client'],
            ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'role' => 'Manager'],
        ]
    );

    $signerToken = $signature->signers->first()->token;

    $signer = $this->service->refuseAsSigner($signerToken, 'Je refuse ce document');

    expect($signer->status)->toBe(SignatureStatus::REFUSED)
        ->and($signer->metadata['refusal_reason'])->toBe('Je refuse ce document')
        ->and($signature->refresh()->status)->toBe(SignatureStatus::REFUSED);
});

it('prevents signing a document that is already refused', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = $this->service->requestMultiSignature(
        $document,
        SignatureType::AUTOGRAPH,
        [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'role' => 'Client'],
        ]
    );

    $signerToken = $signature->signers->first()->token;

    // Refuse first
    $this->service->refuseAsSigner($signerToken);

    // Try to sign the same token — should fail
    $this->expectException(ModelNotFoundException::class);

    $this->service->signAsSigner(
        $signerToken,
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
        '127.0.0.1',
        'TestAgent'
    );
});

it('can get signer count attributes', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = $this->service->requestMultiSignature(
        $document,
        SignatureType::AUTOGRAPH,
        [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'role' => 'Client'],
            ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'role' => 'Manager'],
            ['name' => 'Pierre Durand', 'email' => 'pierre@example.com', 'role' => 'Comptable'],
        ]
    );

    expect($signature->is_multi_signatory)->toBeTrue()
        ->and($signature->total_signers)->toBe(3)
        ->and($signature->signed_count)->toBe(0);

    // Sign one
    $this->service->signAsSigner(
        $signature->signers->first()->token,
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
        '127.0.0.1',
        'TestAgent'
    );

    $signature->refresh();
    expect($signature->signed_count)->toBe(1);
});

it('backward compatible with legacy single signer request', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = $this->service->driver()->requestSignature(
        $document,
        SignatureType::AUTOGRAPH,
        'legacy@example.com',
        'Legacy User',
        '/tmp/test.pdf'
    );

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signers)->toHaveCount(1)
        ->and($signature->signers->first()->email)->toBe('legacy@example.com');
});

it('can create SignatureSigner via factory', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = Signature::factory()->for($document, 'signable')->create([
        'status' => SignatureStatus::PENDING,
        'token' => 'test-token-123',
    ]);

    $signer = SignatureSigner::factory()->for($signature)->create([
        'name' => 'Test Signer',
        'email' => 'test@example.com',
    ]);

    expect($signer->name)->toBe('Test Signer')
        ->and($signer->email)->toBe('test@example.com')
        ->and($signer->status)->toBe(SignatureStatus::PENDING)
        ->and($signer->signature_id)->toBe($signature->id);
});

it('can use signed/refused factory states', function () {
    $thirdParty = ThirdParty::factory()->create();
    $document = ThirdPartyDocument::factory()->create([
        'third_party_id' => $thirdParty->id,
        'type' => 'contrat_sous_traitance',
    ]);

    $signature = Signature::factory()->for($document, 'signable')->create([
        'status' => SignatureStatus::PENDING,
    ]);

    $signer = SignatureSigner::factory()->signed()->for($signature)->create();
    expect($signer->status)->toBe(SignatureStatus::SIGNED)
        ->and($signer->signed_at)->not->toBeNull();

    $signer = SignatureSigner::factory()->refused()->for($signature)->create();
    expect($signer->status)->toBe(SignatureStatus::REFUSED);
});
