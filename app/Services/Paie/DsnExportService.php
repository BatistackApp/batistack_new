<?php

namespace App\Services\Paie;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DsnExportService
{
    /**
     * Generate a simplified CSV export for DADS/DSN
     */
    public function generateCsv(Collection $payslips): string
    {
        $filename = 'export_dads_dsn_' . Carbon::now()->format('Y_m_d_His') . '.csv';
        
        $csvData = [];
        // Header
        $csvData[] = [
            'Matricule',
            'Nom',
            'Prenom',
            'N_Secu',
            'Periode',
            'Heures_Base',
            'Heures_Sup',
            'Salaire_Brut',
            'Net_Social',
            'Net_Imposable',
            'Taux_PAS',
            'Montant_PAS',
            'Net_Paye',
            'Cout_Global_Employeur'
        ];

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $csvData[] = [
                $employee->id,
                $employee->last_name,
                $employee->first_name,
                $employee->social_security_number,
                $payslip->period,
                $payslip->base_hours,
                $payslip->overtime_hours,
                $payslip->gross_salary,
                $payslip->net_social,
                $payslip->taxable_net,
                $payslip->pas_rate,
                $payslip->pas_amount,
                $payslip->net_paid,
                $payslip->employer_cost
            ];
        }

        $output = fopen('php://temp', 'r+');
        // UTF-8 BOM pour Excel
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        foreach ($csvData as $row) {
            fputcsv($output, $row, ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $path = 'documents/exports/' . $filename;
        Storage::disk('public')->put($path, $content);

        return $path;
    }
}
