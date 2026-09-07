<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Core\PdfStamperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

uses(RefreshDatabase::class);

function stampedSignatureData(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
}

function dummyPdfForStamp(): string
{
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Original');
    $temp = sys_get_temp_dir().'/stamp_source_'.Str::uuid().'.pdf';
    $pdf->Output('F', $temp);

    return $temp;
}

function makeStampedSignature(ThirdPartyDocument $document): Signature
{
    return Signature::create([
        'signable_type' => $document->getMorphClass(),
        'signable_id' => $document->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::SIGNED,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
        'signed_at' => now(),
        'signature_data' => stampedSignatureData(),
    ]);
}

it('keeps the source media collection untouched when stamping', function () {
    $sourcePdf = dummyPdfForStamp();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $document->addMedia($sourcePdf)->toMediaCollection('third_party_documents');

    $signature = makeStampedSignature($document);

    $signature->signable->stampSignatureDocument($signature);

    expect($document->hasMedia('third_party_documents'))->toBeTrue()
        ->and($document->hasMedia('signed_documents'))->toBeTrue()
        ->and($document->getSignaturePath())->toBe($document->getMedia('third_party_documents')->first()?->getPath())
        ->and($document->getStampedDocumentPath())->toBe($document->getMedia('signed_documents')->first()?->getPath());

    @unlink($sourcePdf);
});

it('exposes distinct source and stamped urls', function () {
    $sourcePdf = dummyPdfForStamp();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $document->addMedia($sourcePdf)->toMediaCollection('third_party_documents');

    $signature = makeStampedSignature($document);
    $signature->signable->stampSignatureDocument($signature);

    $sourceUrl = $document->getSignatureDocumentUrl($signature);
    $stampedUrl = $document->getStampedDocumentUrl($signature);

    expect($stampedUrl)->not->toBeNull()
        ->and($sourceUrl)->not->toBe($stampedUrl);

    @unlink($sourcePdf);
});

it('does not accumulate certificate pages on re-stamping from source', function () {
    $sourcePdf = dummyPdfForStamp();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $document->addMedia($sourcePdf)->toMediaCollection('third_party_documents');

    $firstSignature = makeStampedSignature($document);
    $secondSignature = makeStampedSignature($document);

    $firstSignature->signable->stampSignatureDocument($firstSignature);
    $secondSignature->signable->stampSignatureDocument($secondSignature);

    $stampedPath = $document->getStampedDocumentPath();
    expect($stampedPath)->not->toBeNull();

    $check = new Fpdi;
    $pageCount = $check->setSourceFile($stampedPath);

    // 1 page source + 1 page certificat => jamais de certificats cumulés.
    expect($pageCount)->toBe(2);

    @unlink($sourcePdf);
});

it('keeps a file-based stamped path distinct from the source for CustomerQuote', function () {
    Storage::fake('public');

    $thirdParty = ThirdParty::factory()->create();
    $quote = \App\Models\Commerce\CustomerQuote::factory()->create([
        'client_id' => $thirdParty->id,
        'reference' => 'DEV-00123',
    ]);

    Storage::disk('public')->put('documents/commerce/quotes/devis_DEV-00123.pdf', '%PDF source');

    $signature = Signature::create([
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'user_id' => User::factory()->create()->id,
        'type' => SignatureType::AUTOGRAPH,
        'status' => SignatureStatus::SIGNED,
        'token' => Str::uuid()->toString(),
        'checksum' => hash('sha256', 'test'),
        'signed_at' => now(),
        'signature_data' => stampedSignatureData(),
    ]);

    // Avant tamponnage, pas de copie signée : on retombe sur la source.
    expect($quote->getSignaturePath())->not->toBeNull()
        ->and($quote->getStampedDocumentPath($signature))->toBe($quote->getSignaturePath());

    // Une fois le PDF signé enregistré, le chemin signé devient distinct de la source.
    $signedRelative = 'documents/commerce/quotes/signes/devis_DEV-00123.pdf';
    Storage::disk('public')->put($signedRelative, '%PDF signé');

    expect($quote->getStampedDocumentPath($signature))
        ->toBe(Storage::disk('public')->path($signedRelative))
        ->not->toBe($quote->getSignaturePath());

    // L'URL signée pointe vers la copie signée (sous-répertoire signes/), pas la source.
    expect($quote->getStampedDocumentUrl($signature))
        ->toContain('signes/devis_DEV-00123.pdf')
        ->not->toBe($quote->getSignatureUrl($signature));
});