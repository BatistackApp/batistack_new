<?php

namespace App\Jobs\Paie;

use App\Models\Paie\Payslip;
use App\Services\Paie\PayslipPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GeneratePayslipPdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payslip $payslip
    ) {}

    public function handle(PayslipPdfService $pdfService): void
    {
        $this->payslip->refresh();

        if ($this->payslip->pdf_path) {
            return;
        }

        $pdfService->generatePdf($this->payslip);
    }
}
