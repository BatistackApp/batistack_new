<?php

namespace App\Jobs\Laser;

use App\Mail\Laser\LaserInvoiceMail;
use App\Models\Laser\LaserInvoice;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLaserInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $invoiceId,
        public string $email,
    ) {}

    public function handle(LaserDocumentationService $documentationService): void
    {
        $invoice = LaserInvoice::with(['client', 'order', 'lines.material'])->findOrFail($this->invoiceId);
        $pdfPath = $documentationService->generateInvoicePdf($invoice);

        Mail::to($this->email)->send(new LaserInvoiceMail($invoice, $pdfPath));
    }
}
