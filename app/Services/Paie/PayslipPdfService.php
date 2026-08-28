<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayslipStatus;
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

        // Calcul des cumuls annuels réels
        $year = substr($payslip->period, 0, 4);
        $historicalPayslips = Payslip::where('employee_id', $payslip->employee_id)
            ->where('period', '>=', $year.'-01')
            ->where('period', '<', $payslip->period)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->get();

        $annualTotals = [
            'base_hours' => $payslip->base_hours,
            'overtime_hours' => $payslip->overtime_hours,
            'gross_salary' => $payslip->gross_salary,
            'taxable_net' => $payslip->taxable_net,
            'employer_cost' => $payslip->employer_cost,
            'pas_amount' => $payslip->pas_amount,
            'exonerations' => 939.74, // Basé sur le forfait simulé actuel de Fillon
        ];

        foreach ($historicalPayslips as $hp) {
            $annualTotals['base_hours'] += $hp->base_hours;
            $annualTotals['overtime_hours'] += $hp->overtime_hours;
            $annualTotals['gross_salary'] += $hp->gross_salary;
            $annualTotals['taxable_net'] += $hp->taxable_net;
            $annualTotals['employer_cost'] += $hp->employer_cost;
            $annualTotals['pas_amount'] += $hp->pas_amount;
            $annualTotals['exonerations'] += 939.74;
        }

        // Calcul des charges patronales (estimation basée sur le delta brut -> cout global)
        $annualTotals['employer_contributions'] = $annualTotals['employer_cost'] - $annualTotals['gross_salary'] - $annualTotals['exonerations'];

        $data['annualTotals'] = $annualTotals;

        $filename = 'payslip_'.$payslip->period.'_'.$payslip->employee->last_name;

        // Le chemin relatif au disque public
        $relativePath = 'documents/payslips/'.$filename.'.pdf';

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
