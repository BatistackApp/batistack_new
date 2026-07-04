<?php

use App\Mail\Core\SignatureRequestedMail;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the signature requested mail correctly', function () {
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
    ]);

    $mail = new SignatureRequestedMail($signature, 'John Doe');
    
    $mail->assertSeeInHtml('Signer le Document');
    $mail->assertSeeInHtml('John Doe');
    
    expect($mail->attachments())->toBeEmpty();
});

it('attaches document if provided', function () {
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
    ]);

    // Dummy path
    $tempFile = sys_get_temp_dir() . '/dummy.pdf';
    file_put_contents($tempFile, 'dummy');

    $mail = new SignatureRequestedMail($signature, 'John Doe', $tempFile);
    
    expect($mail->attachments())->toHaveCount(1);
    
    unlink($tempFile);
});
