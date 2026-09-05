<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\User;
use App\Services\Core\Providers\DocusealProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Notification::fake();
    config()->set('signature.providers.docuseal.api_token', 'test-token');
    config()->set('signature.providers.docuseal.api_url', 'https://api.docuseal.com');
    $this->user = User::factory()->create();
    $this->provider = new DocusealProvider;
    $this->quote = CustomerQuote::factory()->create();
});

it('creates signature with signers via requestMultiSignature', function () {
    Http::fake([
        'api.docuseal.com/templates/pdf' => Http::response(['id' => 123], 200),
        'api.docuseal.com/submissions' => Http::response([['submission_id' => 'sub-123']], 200),
    ]);

    Storage::fake('local');
    $pdfPath = 'signatures/test.pdf';
    Storage::disk('local')->put($pdfPath, '%PDF-1.4 fake content');

    $signature = $this->provider->requestMultiSignature(
        $this->quote,
        SignatureType::AUTOGRAPH,
        [
            ['name' => 'Alice', 'email' => 'alice@test.com'],
            ['name' => 'Bob', 'email' => 'bob@test.com'],
        ],
        $pdfPath
    );

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signers)->toHaveCount(2)
        ->and($signature->metadata['signers_count'])->toBe(2);

    expect($signature->signers->first()->name)->toBe('Alice')
        ->and($signature->signers->first()->status)->toBe(SignatureStatus::PENDING)
        ->and($signature->signers->first()->token)->not->toBeNull();
});

it('throws on requestSignature without email', function () {
    $this->provider->requestSignature($this->quote, SignatureType::AUTOGRAPH, null, null);
})->throws(InvalidArgumentException::class, 'Email et Nom requis');

it('throws on requestSignature without name', function () {
    $this->provider->requestSignature($this->quote, SignatureType::AUTOGRAPH, 'test@test.com', null);
})->throws(InvalidArgumentException::class, 'Email et Nom requis');

it('signs as specific signer via signAsSigner', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signerToken = Str::uuid()->toString();
    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => $signerToken,
    ]);

    // Create a second signer that stays PENDING
    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Bob',
        'email' => 'bob@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
    ]);

    $result = $this->provider->signAsSigner($signerToken, 'base64data', '1.2.3.4', 'Mozilla');

    expect($result->status)->toBe(SignatureStatus::SIGNED)
        ->and($result->signed_at)->not->toBeNull()
        ->and($result->metadata['source'])->toBe('external_public_link');

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::PENDING);
});

it('completes signature when all signers have signed', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $token1 = Str::uuid()->toString();
    $token2 = Str::uuid()->toString();

    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => $token1,
    ]);

    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Bob',
        'email' => 'bob@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => $token2,
    ]);

    $this->provider->signAsSigner($token1, 'base64data1', '1.2.3.4', 'Mozilla');
    $this->provider->signAsSigner($token2, 'base64data2', '5.6.7.8', 'Chrome');

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::SIGNED)
        ->and($signature->signed_at)->not->toBeNull();
});

it('refuses as signer and stops workflow', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signerToken = Str::uuid()->toString();
    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => $signerToken,
    ]);

    $result = $this->provider->refuseAsSigner($signerToken, 'Je refuse');

    expect($result->status)->toBe(SignatureStatus::REFUSED)
        ->and($result->metadata['refusal_reason'])->toBe('Je refuse');

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::REFUSED);
});

it('refuseAsSigner throws on non-pending signer', function () {
    $signerToken = Str::uuid()->toString();
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::SIGNED,
        'token' => $signerToken,
    ]);

    $this->provider->refuseAsSigner($signerToken);
})->throws(ModelNotFoundException::class);

it('creates new signature via sign (legacy single signer)', function () {
    actingAs($this->user);

    $result = $this->provider->sign(
        $this->quote,
        'base64data',
        SignatureType::AUTOGRAPH,
        ['source' => 'test']
    );

    expect($result->status)->toBe(SignatureStatus::SIGNED)
        ->and($result->signature_data)->toBe('base64data')
        ->and($result->signed_at)->not->toBeNull()
        ->and($result->metadata['source'])->toBe('test');
});

it('updates existing pending signature via sign', function () {
    $existing = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $result = $this->provider->sign($this->quote, 'base64data', SignatureType::AUTOGRAPH);

    expect($result->id)->toBe($existing->id)
        ->and($result->status)->toBe(SignatureStatus::SIGNED)
        ->and($result->metadata['source'])->toBe('docuseal_webhook');
});

it('verify returns true for signed signature', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    expect($this->provider->verify($signature))->toBeTrue();
});

it('verify returns false for pending signature', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    expect($this->provider->verify($signature))->toBeFalse();
});
