<?php

use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Core\PdfStamperService;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stamps a pdf correctly', function () {
    // Generate a dummy PDF
    $pdf = new Fpdi();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Hello World!');
    $tempPdfPath = sys_get_temp_dir() . '/dummy_original_' . Str::uuid() . '.pdf';
    $pdf->Output('F', $tempPdfPath);

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
        'signed_at' => now(),
        'ip_address' => '127.0.0.1',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        'metadata' => ['user_agent' => 'Test Agent']
    ]);

    $stamper = new PdfStamperService();
    $stampedPath = $stamper->stamp($tempPdfPath, $signature, 'John Doe');

    expect(file_exists($stampedPath))->toBeTrue();
    
    $checkPdf = new Fpdi();
    $pageCount = $checkPdf->setSourceFile($stampedPath);
    expect($pageCount)->toBeGreaterThan(1);

    @unlink($tempPdfPath);
    @unlink($stampedPath);
});
