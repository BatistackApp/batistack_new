<?php

namespace App\Services\Paie;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AccountingExportService
{
    /**
     * Generate OD CSV export for accounting
     */
    public function generateCsv(Collection $payslips): string
    {
        $filename = 'export_od_paie_' . Carbon::now()->format('Y_m_d_His') . '.csv';
        
        $csvData = [];
        // En-tête : Journal, Date, Compte, Libellé, Débit, Crédit
        $csvData[] = ['Journal', 'Date', 'Compte', 'Libelle', 'Debit', 'Credit'];

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $date = Carbon::parse($payslip->period . '-01')->endOfMonth()->format('d/m/Y');
            $libelleBase = 'Paie ' . $payslip->period . ' - ' . $employee->last_name . ' ' . $employee->first_name;

            // Calculs préalables
            // Les primes non soumises (GD, Repas, Frais)
            $nonTaxableAllowances = $payslip->gd_allowance_amount + $payslip->meal_allowance_amount + $payslip->expense_reports_amount;
            
            // Si l'utilisateur a d'autres primes non soumises
            if (is_array($payslip->custom_bonuses)) {
                foreach ($payslip->custom_bonuses as $bonus) {
                    if (empty($bonus['is_taxable']) || !$bonus['is_taxable']) {
                        $nonTaxableAllowances += (float)$bonus['amount'];
                    }
                }
            }

            $grossPlusAllowances = $payslip->gross_salary + $nonTaxableAllowances;
            $employerCharges = $payslip->employer_cost - $payslip->gross_salary;
            $employeeCharges = $payslip->gross_salary - $payslip->net_social;
            
            // Acomptes liés
            $payslip->loadMissing('advances');
            $advancesTotal = $payslip->advances->sum('amount');

            // ----------------------------------------------------
            // Écritures au DÉBIT (Charges)
            // ----------------------------------------------------
            
            // 641100 - Salaires, appointements (Brut + Indemnités non soumises)
            $csvData[] = ['OD', $date, '641100', $libelleBase . ' (Salaires)', number_format($grossPlusAllowances, 2, '.', ''), '0.00'];

            // 645000 - Charges de Sécurité Sociale et Prévoyance (Part Patronale)
            if ($employerCharges > 0) {
                $csvData[] = ['OD', $date, '645000', $libelleBase . ' (Charges Pat.)', number_format($employerCharges, 2, '.', ''), '0.00'];
            }

            // ----------------------------------------------------
            // Écritures au CRÉDIT (Dettes)
            // ----------------------------------------------------
            
            // 431000 - Sécurité Sociale et autres organismes sociaux (Salarial + Patronal)
            $totalSocial = $employeeCharges + $employerCharges;
            if ($totalSocial > 0) {
                $csvData[] = ['OD', $date, '431000', $libelleBase . ' (Organismes sociaux)', '0.00', number_format($totalSocial, 2, '.', '')];
            }

            // 442100 - Prélèvement à la source (PAS)
            if ($payslip->pas_amount > 0) {
                $csvData[] = ['OD', $date, '442100', $libelleBase . ' (PAS)', '0.00', number_format($payslip->pas_amount, 2, '.', '')];
            }

            // 425000 - Personnel - Avances et acomptes
            if ($advancesTotal > 0) {
                $csvData[] = ['OD', $date, '425000', $libelleBase . ' (Reprise Acomptes)', '0.00', number_format($advancesTotal, 2, '.', '')];
            }

            // 421000 - Personnel - Rémunérations dues (Net à payer final)
            // net_paid = (net_social - PAS) - acomptes + indemnités_non_soumises
            $csvData[] = ['OD', $date, '421000', $libelleBase . ' (Net dû)', '0.00', number_format($payslip->net_paid, 2, '.', '')];
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
