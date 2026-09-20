<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Laser\QuoteStatus;
use App\Models\Core\Company;
use App\Models\Core\Signature;
use App\Models\Laser\LaserQuote;
use App\Services\Core\PdfStamperService;
use App\Services\Core\SignatureChecksumService;
use App\Services\Core\SignatureService;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

function localSignaturePdf(): string
{
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Devis Laser');
    $path = sys_get_temp_dir().'/laser_quote_'.Str::uuid().'.pdf';
    $pdf->Output('F', $path);

    return $path;
}

it('completes the local signature workflow before converting a quote', function () {
    Storage::fake('public');

    Company::factory()->create();

    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::DRAFT]);
    $quote->update(['status' => QuoteStatus::SENT]);
    $relativePath = 'documents/laser/quotes/devis_laser_'.$quote->reference.'.pdf';
    $sourcePath = localSignaturePdf();
    Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

    $signature = app(SignatureService::class)->driver()->requestSignature(
        $quote,
        SignatureType::AUTOGRAPH,
        'client@example.com',
        'Client Test',
        $relativePath,
    );
    $signer = $signature->signers()->firstOrFail();

    $this->post(route('signature.sign', $signer->token), [
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
    ])->assertRedirect();

    $quote->refresh();
    $signature->refresh();

    expect($signature->status)->toBe(SignatureStatus::SIGNED)
        ->and($quote->status)->toBe(QuoteStatus::ACCEPTED)
        ->and($quote->signed_at)->not->toBeNull()
        ->and(Storage::disk('public')->exists('documents/laser/quotes/signes/devis_laser_'.$quote->reference.'.pdf'))->toBeTrue();

    $order = app(LaserQuoteService::class)->convertToOrder($quote);

    expect($order->laser_quote_id)->toBe($quote->id);

    @unlink($sourcePath);
});

it('does not expose a stamped PDF when stamping fails', function () {
    Storage::fake('public');
    Company::factory()->create();
    $quote = LaserQuote::factory()->create(['status' => QuoteStatus::ACCEPTED, 'signed_at' => now()]);
    $relativePath = 'documents/laser/quotes/devis_laser_'.$quote->reference.'.pdf';
    $sourcePath = localSignaturePdf();
    Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

    $signature = Signature::create([
        'token' => Str::uuid()->toString(),
        'signable_type' => $quote->getMorphClass(),
        'signable_id' => $quote->id,
        'status' => SignatureStatus::SIGNED,
        'type' => SignatureType::AUTOGRAPH,
        'checksum' => app(SignatureChecksumService::class)->generate($quote),
        'signed_at' => $quote->signed_at,
        'metadata' => ['provider' => 'local'],
    ]);

    $stamper = Mockery::mock(PdfStamperService::class);
    $stamper->shouldReceive('stamp')->once()->andThrow(new RuntimeException('Stamping failed'));
    app()->instance(PdfStamperService::class, $stamper);

    expect(fn () => $quote->stampSignatureDocument($signature))
        ->toThrow(RuntimeException::class, 'Stamping failed');

    expect(Storage::disk('public')->exists('documents/laser/quotes/signes/devis_laser_'.$quote->reference.'.pdf'))->toBeFalse();

    @unlink($sourcePath);
});
