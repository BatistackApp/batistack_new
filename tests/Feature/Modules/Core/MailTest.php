<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Mail\Articles\SupplierQuoteRequestMail;
use App\Mail\Core\SignatureRequestedMail;
use App\Models\Articles\Item;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the supplier quote request mail correctly', function () {
    $item = Item::factory()->create([
        'reference' => 'REF-001',
        'name' => 'Ciment',
    ]);

    $mail = new SupplierQuoteRequestMail($item);

    $mail->assertSeeInHtml('Demande de prix et disponibilité');
    $mail->assertSeeInHtml('REF-001');
    $mail->assertSeeInHtml('Ciment');

    expect($mail->attachments())->toBeEmpty();
});

it('renders the signature requested mail correctly', function () {
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
    ]);

    $mail = new SignatureRequestedMail($signature, 'John Doe');

    $mail->assertSeeInHtml('Signer le Document');
    $mail->assertSeeInHtml('John Doe');

    expect($mail->attachments())->toBeEmpty();
});

it('attaches document if provided', function () {
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
    ]);

    // Dummy path
    $tempFile = sys_get_temp_dir().'/dummy.pdf';
    file_put_contents($tempFile, 'dummy');

    $mail = new SignatureRequestedMail($signature, 'John Doe', $tempFile);

    expect($mail->attachments())->toHaveCount(1);

    unlink($tempFile);
});
