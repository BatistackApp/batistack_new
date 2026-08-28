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
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

uses(RefreshDatabase::class);

it('stamps a pdf correctly', function () {
    // Generate a dummy PDF
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Hello World!');
    $tempPdfPath = sys_get_temp_dir().'/dummy_original_'.Str::uuid().'.pdf';
    $pdf->Output('F', $tempPdfPath);

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
        'signed_at' => now(),
        'ip_address' => '127.0.0.1',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        'metadata' => ['user_agent' => 'Test Agent'],
    ]);

    $stamper = new PdfStamperService;
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'John Doe');

    expect(file_exists($stampedPath))->toBeTrue();

    $checkPdf = new Fpdi;
    $pageCount = $checkPdf->setSourceFile($stampedPath);
    expect($pageCount)->toBeGreaterThan(1);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});
