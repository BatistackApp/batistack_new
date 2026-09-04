<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Mail\Core\MultiSignatureRequestedMail;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\User;
use App\Notifications\Core\SignatureCompletedNotification;
use App\Notifications\Core\SignatureRefusedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    $this->user = User::factory()->create();
    $this->quote = CustomerQuote::factory()->create();
});

it('sends completed notification via mail channel', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'signed_at' => now(),
        'checksum' => hash('sha256', 'test'),
    ]);

    $notification = new SignatureCompletedNotification($signature);
    expect($notification->via($this->user))->toBe(['mail'])
        ->and($notification->signature)->toBeInstanceOf(Signature::class)
        ->and($notification->signature->status)->toBe(SignatureStatus::SIGNED);
});

it('sends refused notification via mail channel', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'user_id' => $this->user->id,
        'status' => SignatureStatus::REFUSED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signer = SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::REFUSED,
        'token' => Str::uuid()->toString(),
    ]);

    $notification = new SignatureRefusedNotification($signature, $signer);
    expect($notification->via($this->user))->toBe(['mail']);
});

it('sends multi-signature requested mail', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signer = SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
    ]);

    Mail::to('alice@test.com')->send(new MultiSignatureRequestedMail($signer));

    Mail::assertSent(MultiSignatureRequestedMail::class, function ($mail) {
        return $mail->hasTo('alice@test.com')
            && $mail->envelope()->subject === 'Demande de Signature Numérique — '.get_class($this->quote);
    });
});

it('sends multi-signature mail without attachment by default', function () {
    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $this->quote->getMorphClass(),
        'signable_id' => $this->quote->id,
        'status' => SignatureStatus::PENDING,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => hash('sha256', 'test'),
    ]);

    $signer = SignatureSigner::create([
        'signature_id' => $signature->id,
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => SignatureStatus::PENDING,
        'token' => Str::uuid()->toString(),
    ]);

    $mail = new MultiSignatureRequestedMail($signer);
    expect($mail->attachments())->toBeEmpty();
});
