<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Core\PdfStamperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

uses(RefreshDatabase::class);

function dummyPdf(string $label = 'Hello World!'): string
{
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, $label);

    $tempPdfPath = sys_get_temp_dir().'/dummy_original_'.Str::uuid().'.pdf';
    $pdf->Output('F', $tempPdfPath);

    return $tempPdfPath;
}

function signatureData(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
}

function makeSignature(ThirdPartyDocument $document): Signature
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
        'ip_address' => '127.0.0.1',
        'metadata' => ['user_agent' => 'Test Agent'],
    ]);
}

function makeSigner(Signature $signature, ?string $signatureData = null, SignatureStatus $status = SignatureStatus::SIGNED): SignatureSigner
{
    return SignatureSigner::factory()->for($signature)->create([
        'name' => 'Signataire '.Str::random(4),
        'role' => 'Témoin',
        'status' => $status->value,
        'signature_data' => $signatureData,
    ]);
}

it('stamps a pdf correctly', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)
        ->forceFill(['signature_data' => signatureData()]);

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'John Doe');

    expect(file_exists($stampedPath))->toBeTrue();

    $checkPdf = new Fpdi;
    $pageCount = $checkPdf->setSourceFile($stampedPath);
    expect($pageCount)->toBeGreaterThan(1);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('computes the SHA-256 of the signed body (source document)', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)
        ->forceFill(['signature_data' => signatureData()]);

    $stamper = new PdfStamperService;
    $checksum = null;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'John Doe', null, $checksum);

    $expected = hash_file('sha256', $tempPdfPath);
    expect($checksum)->toBe($expected);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('ignores a signer without signature_data', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => null]);
    $signerWithData = makeSigner($signature, signatureData());
    makeSigner($signature, null);

    $stamper = new PdfStamperService;
    $checksum = null;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, null, [$signerWithData], $checksum);

    expect(file_exists($stampedPath))->toBeTrue()
        ->and($checksum)->toBe(hash_file('sha256', $tempPdfPath));

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('falls back to parent signature_data for legacy single signer', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => signatureData()]);

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'Jane Legacy');

    expect(file_exists($stampedPath))->toBeTrue();

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('cleans up temporary signature images', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => signatureData()]);
    $signer = makeSigner($signature, signatureData());

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, null, [$signer]);

    // The temporary images must have been deleted in the finally block.
    $tempImages = glob(sys_get_temp_dir().'/signature_*.png');
    expect($tempImages ?: [])->toBe([]);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('preserves the original page count', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => signatureData()]);

    $sourcePdf = new Fpdi;
    $sourcePageCount = $sourcePdf->setSourceFile($tempPdfPath);

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'John Doe');

    $checkPdf = new Fpdi;
    $stampedPageCount = $checkPdf->setSourceFile($stampedPath);

    expect($stampedPageCount)->toBe($sourcePageCount + 1); // original + certificate page

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});

it('stamps the certificate grid with many signers', function (int $signerCount) {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => null]);

    $signers = [];
    for ($i = 0; $i < $signerCount; $i++) {
        $signers[] = makeSigner($signature, signatureData());
    }

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, null, $signers);

    expect(file_exists($stampedPath))->toBeTrue();

    $checkPdf = new Fpdi;
    $pageCount = $checkPdf->setSourceFile($stampedPath);
    expect($pageCount)->toBeGreaterThan(1);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
})->with([1, 2, 3, 4, 5, 6, 10]);

it('renders a multi page certificate when signers overflow one page', function () {
    $tempPdfPath = dummyPdf();

    $document = ThirdPartyDocument::create([
        'type' => ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE,
        'status' => ThirdPartyDocumentStatus::VALID,
        'third_party_id' => ThirdParty::factory()->create()->id,
    ]);

    $signature = makeSignature($document)->forceFill(['signature_data' => null]);

    $signers = [];
    for ($i = 0; $i < 12; $i++) {
        $signers[] = makeSigner($signature, signatureData());
    }

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, null, $signers);

    $checkPdf = new Fpdi;
    $pageCount = $checkPdf->setSourceFile($stampedPath);

    // 12 signers → 6 rows → 73mm/row → 3 pages (1 original + certificate pages)
    expect($pageCount)->toBeGreaterThan(2);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});