<?php

namespace App\Services\Paie;

use App\Models\Paie\Payslip;
use App\Services\Core\DocumentService;

class PayslipPdfService
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Generate the PDF for a Payslip
     */
    public function generatePdf(Payslip $payslip): string
    {
        $payslip->load(['employee', 'lines', 'advances']);
        
        $data = [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'lines' => $payslip->lines->groupBy('category'),
            'advances' => $payslip->advances,
        ];

        $filename = 'payslip_' . $payslip->period . '_' . $payslip->employee->last_name;

        // Le chemin relatif au disque public
        $relativePath = 'documents/payslips/' . $filename . '.pdf';

        $this->documentService->generate(
            view: 'pdf.payslip',
            data: $data,
            filename: $filename,
            type: 'payslips'
        );

        $payslip->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }
}
