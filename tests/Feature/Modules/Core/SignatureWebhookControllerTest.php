<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\User;
use App\Services\Core\SignatureService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    Notification::fake();
    config()->set('signature.providers.docuseal.api_token', 'test-token');
    config()->set('signature.providers.docuseal.api_url', 'https://api.docuseal.com');
    $this->user = User::factory()->create();
    $this->service = app(SignatureService::class);
});

it('returns 400 on invalid payload', function () {
    $response = $this->postJson(route('webhooks.docuseal'), []);
    $response->assertStatus(400);
});

it('returns success on unknown event type', function () {
    $response = $this->postJson(route('webhooks.docuseal'), [
        'event_type' => 'submission.created',
        'data' => ['id' => '123'],
    ]);

    $response->assertOk();
});

it('processes legacy single signer webhook', function () {
    $quote = CustomerQuote::factory()->create();
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::EIDAS,
        'checksum' => hash('sha256', 'test'),
        'metadata' => ['provider' => 'docuseal', 'docuseal_submission_id' => 'sub-123'],
    ]);

    $response = $this->postJson(route('webhooks.docuseal'), [
        'event_type' => 'submission.completed',
        'data' => [
            'id' => 'sub-123',
            'document_url' => 'https://example.com/signed.pdf',
        ],
    ]);

    $response->assertOk();
});

it('processes multi-signer webhook for each signer', function () {
    $quote = CustomerQuote::factory()->create();
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::EIDAS,
        'checksum' => hash('sha256', 'test'),
        'metadata' => ['provider' => 'docuseal', 'docuseal_submission_id' => 'sub-456'],
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

    $response = $this->postJson(route('webhooks.docuseal'), [
        'event_type' => 'submission.completed',
        'data' => [
            'id' => 'sub-456',
            'document_url' => 'https://example.com/signed.pdf',
            'submitters' => [
                ['email' => 'alice@test.com', 'name' => 'Alice'],
                ['email' => 'bob@test.com', 'name' => 'Bob'],
            ],
        ],
    ]);

    $response->assertOk();
});

it('ignores webhook with no matching signature', function () {
    $response = $this->postJson(route('webhooks.docuseal'), [
        'event_type' => 'submission.completed',
        'data' => [
            'id' => 'nonexistent-submission-id',
            'document_url' => 'https://example.com/signed.pdf',
        ],
    ]);

    $response->assertOk();
});

it('skips submitters without email', function () {
    $quote = CustomerQuote::factory()->create();
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::EIDAS,
        'checksum' => hash('sha256', 'test'),
        'metadata' => ['provider' => 'docuseal', 'docuseal_submission_id' => 'sub-789'],
    ]);

    SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
    ]);

    $response = $this->postJson(route('webhooks.docuseal'), [
        'event_type' => 'submission.completed',
        'data' => [
            'id' => 'sub-789',
            'document_url' => 'https://example.com/signed.pdf',
            'submitters' => [
                ['name' => 'NoEmail'],
                ['email' => 'alice@test.com', 'name' => 'Alice'],
            ],
        ],
    ]);

    $response->assertOk();
});
