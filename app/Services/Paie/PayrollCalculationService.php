<?php

namespace App\Services\Paie;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\ContributionBaseFormula;
use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\AdvancePayment;
use App\Models\Paie\PayrollContributionProfile;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;

class PayrollCalculationService
{
    /**
     * Calcule la fiche de paie pour un employé et un mois donné
     */
    public function calculateForEmployee(Employee $employee, string $period, float $baseHours, float $hourlyRate, array $customBonuses = []): Payslip
    {
        // Extraction de l'année et du mois depuis $period (ex: "2026-07")
        $parts = explode('-', $period);
        $year = $parts[0];
        $month = $parts[1] ?? '01';

        // 0. Récupérer le profil de cotisation (nécessaire tôt pour le montant du panier)
        $contract = $employee->currentContract;
        $profile = $contract ? $contract->payrollContributionProfile : null;

        if (!$profile) {
            throw new \Exception("Aucun profil de cotisations défini pour le contrat en cours de l'employé {$employee->last_name}.");
        }

        // --- LECTURE DES DONNÉES RH (POINTAGES & FRAIS) ---
        // 1. Pointages du mois
        $timeEntries = \App\Models\RH\TimeEntry::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', \App\Enums\RH\TimeEntryStatus::APPROVED)
            ->get();

        $workedHours = $timeEntries->sum('hours');
        
        // Les heures supplémentaires sont celles pointées explicitement avec le type OVERTIME_25
        $overtimeHours = $timeEntries->where('type', \App\Enums\RH\TimeEntryType::OVERTIME_25)->sum('hours');
        
        // Calcul du montant des heures supplémentaires (majoration standard 25%)
        $overtimeAmount = round($overtimeHours * $hourlyRate * 1.25, 2);
        
        // Calcul des indemnités de grand déplacement
        $gdAllowancesAmount = $timeEntries->sum('gd_allowance_amount');

        // Calcul des Paniers (Jours sans grand déplacement)
        $mealAllowanceDays = $timeEntries->filter(function ($entry) {
            return !$entry->is_grand_deplacement;
        })->groupBy(function ($entry) {
            return $entry->date->format('Y-m-d');
        })->count();
        $mealAllowanceTotal = round($mealAllowanceDays * ($profile->meal_allowance_amount ?? 0), 2);

        // Temps de trajet
        $travelHours = $timeEntries->sum('travel_hours');
        if ($travelHours > 0) {
            $travelAmount = round($travelHours * $hourlyRate, 2);
            $customBonuses[] = [
                'label' => 'Temps de trajet (' . number_format($travelHours, 2) . 'h)',
                'amount' => $travelAmount,
                'is_taxable' => true,
            ];
        }

        // 2. Notes de Frais
        $expenseReportsAmount = \App\Models\RH\ExpenseReport::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('month', (int) $month)
            ->where('status', \App\Enums\RH\ExpenseReportStatus::PAID) // Supposons que PAID existe
            ->sum('total_amount');

        // --- GESTION DES PRIMES MANUELLES ---
        $taxableBonuses = 0;
        $nonTaxableBonuses = 0;
        foreach ($customBonuses as $bonus) {
            $amount = (float) ($bonus['amount'] ?? 0);
            if (!empty($bonus['is_taxable'])) {
                $taxableBonuses += $amount;
            } else {
                $nonTaxableBonuses += $amount;
            }
        }

        // --- CALCUL DU BRUT ---
        $baseSalary = round($baseHours * $hourlyRate, 2);
        $grossSalary = $baseSalary + $overtimeAmount + $taxableBonuses;

        // 3. Initialiser le Payslip
        $payslip = new Payslip([
            'employee_id' => $employee->id,
            'period' => $period,
            'base_hours' => $baseHours,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'gd_allowance_amount' => $gdAllowancesAmount,
            'expense_reports_amount' => $expenseReportsAmount,
            'meal_allowance_amount' => $mealAllowanceTotal,
            'custom_bonuses' => $customBonuses,
            'hourly_rate' => $hourlyRate,
            'gross_salary' => $grossSalary,
            'status' => PayslipStatus::DRAFT,
            'net_social' => 0,
            'taxable_net' => 0,
            'net_payable' => 0,
            'net_paid' => 0,
            'employer_cost' => 0,
        ]);

        $payslip->save();

        $totalEmployeeContributions = 0;
        $totalEmployerContributions = 0;
        
        // --- PRE-CALCUL REINTEGRATION FISCALE ---
        // La CSG et le Net Imposable nécessitent de connaître les parts patronales Mutuelle/Prévoyance
        $fiscalReintegration = 0;
        if ($profile) {
            foreach ($profile->rates as $rate) {
                if ($rate->is_fiscally_reintegrated && $rate->base_formula === ContributionBaseFormula::GROSS_SALARY) {
                    $fiscalReintegration += round($grossSalary * ($rate->employer_rate / 100), 2);
                }
            }
        }

        $csgBase = round($grossSalary * 0.9825, 2); // Base CSG standard (98.25% du brut)
        $csgBase += $fiscalReintegration; // Ajout dynamique de la part patronale Prévoyance/Mutuelle
        
        $totalDeductibleContributions = 0;
        $csgNonDeductibleAmount = 0;

        if ($profile) {
            foreach ($profile->rates as $rate) {
                $base = $grossSalary;
                if ($rate->base_formula === ContributionBaseFormula::CSG_BASE) {
                    $base = $csgBase;
                } elseif ($rate->base_formula === ContributionBaseFormula::OPPBTP_BASE) {
                    $base = round($grossSalary * 1.1394, 2);
                }

                $employeeAmount = round($base * ($rate->employee_rate / 100), 2);
                $employerAmount = round($base * ($rate->employer_rate / 100), 2);

                $payslip->lines()->create([
                    'category' => $rate->category,
                    'label' => $rate->label,
                    'base' => $base,
                    'employee_rate' => $rate->employee_rate,
                    'employer_rate' => $rate->employer_rate,
                    'employee_amount' => $employeeAmount,
                    'employer_amount' => $employerAmount,
                ]);

                $totalEmployeeContributions += $employeeAmount;
                $totalEmployerContributions += $employerAmount;

                if ($rate->is_deductible) {
                    $totalDeductibleContributions += $employeeAmount;
                } else {
                    $csgNonDeductibleAmount += $employeeAmount;
                }
            }
        }

        $exonerationEmployer = 939.74; // Réduction Fillon simulée
        $totalEmployerContributions -= $exonerationEmployer;

        // --- CALCULS DES NETS ---
        $netSocial = round($grossSalary - $totalEmployeeContributions, 2);
        
        // Net imposable = Net social + CSG non déductible + réintégration fiscale (mutuelle/prévoyance)
        $taxableNet = round($netSocial + $csgNonDeductibleAmount + $fiscalReintegration, 2);

        // PAS (Prélèvement à la source)
        $pasRate = $employee->pas_rate ?? 0;
        $pasAmount = round($taxableNet * ($pasRate / 100), 2);

        $netPayable = round($netSocial - $pasAmount, 2);

        // 4. Acomptes
        $advances = AdvancePayment::where('employee_id', $employee->id)
            ->whereIn('status', [AdvancePaymentStatus::APPROVED, AdvancePaymentStatus::PAID])
            ->whereNull('payslip_id')
            ->get();

        $advancesTotal = $advances->sum('amount');

        $netPaid = $netPayable - $advancesTotal + $gdAllowancesAmount + $expenseReportsAmount + $mealAllowanceTotal + $nonTaxableBonuses;

        // Attacher les acomptes
        foreach ($advances as $advance) {
            $advance->update([
                'status' => AdvancePaymentStatus::DEDUCTED,
                'payslip_id' => $payslip->id,
            ]);
        }

        $employerCost = round($grossSalary + $totalEmployerContributions, 2);

        $payslip->update([
            'net_social' => $netSocial,
            'taxable_net' => $taxableNet,
            'pas_rate' => $pasRate,
            'pas_amount' => $pasAmount,
            'net_payable' => $netPayable,
            'net_paid' => $netPaid,
            'employer_cost' => $employerCost,
        ]);

        return $payslip;
    }
}
