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

        // --- GESTION DES ABSENCES (Issue #148 & #241) ---
        $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $absences = \App\Models\RH\Abscence::where('employee_id', $employee->id)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where('start_date', '<', $startOfMonth)
                          ->where('end_date', '>', $endOfMonth);
                    });
            })
            ->get();

        $dailyHours = $contract && $contract->weekly_hours ? round($contract->weekly_hours / 5, 2) : 7.0;
        
        $employeeCategory = $contract->category->value ?? 'ouvrier';
        $ijssBrutesTotal = 0.0;

        foreach ($absences as $absence) {
            $overlapStart = $absence->start_date->copy()->max($startOfMonth);
            $overlapEnd = $absence->end_date->copy()->min($endOfMonth);

            // Règle de carence CC Bâtiment
            $carenceDays = 3; // Par défaut
            $seniorityMonths = $contract->start_date ? $contract->start_date->diffInMonths($absence->start_date) : 0;
            
            if ($absence->type === \App\Enums\RH\AbsenceType::WORK_ACCIDENT) {
                $carenceDays = 0;
            } elseif (in_array($employeeCategory, ['etam', 'cadre']) && $seniorityMonths >= 12) {
                $carenceDays = 0;
            }
            
            $carenceEnd = $absence->start_date->copy()->addDays($carenceDays)->startOfDay();

            // Calculer les jours ouvrés (lundi à vendredi)
            $workingDays = 0;
            $maintainedWorkingDays = 0;
            $current = $overlapStart->copy();
            while ($current <= $overlapEnd) {
                if ($current->isWeekday()) {
                    $workingDays++;
                    if ($current >= $carenceEnd) {
                        $maintainedWorkingDays++;
                    }
                }
                $current = $current->addDay();
            }

            $absenceHours = $workingDays * $dailyHours;

            if ($absenceHours > 0) {
                $deductionAmount = round($absenceHours * $hourlyRate, 2);
                $labelSuffix = ' du ' . $absence->start_date->format('d/m/Y') . ' au ' . $absence->end_date->format('d/m/Y') . ' (' . $workingDays . 'j)';
                
                $customBonuses[] = [
                    'label' => 'Absence ' . $absence->getType()->getLabel() . $labelSuffix,
                    'amount' => -$deductionAmount,
                    'is_taxable' => true,
                ];

                // Maintien de salaire (CC Bâtiment)
                if (in_array($absence->type, [\App\Enums\RH\AbsenceType::SICK_LEAVE, \App\Enums\RH\AbsenceType::WORK_ACCIDENT])) {
                    if ($maintainedWorkingDays > 0) {
                        $maintenanceGross = round($maintainedWorkingDays * $dailyHours * $hourlyRate, 2);
                        
                        // Gestion IJSS (Subrogation)
                        if ($absence->requires_subrogation) {
                            $ijExpected = $absence->ij_expected;
                            if (!$ijExpected || $ijExpected == 0) {
                                // Calcul auto approximatif des IJSS pour la période (50% du salaire journalier)
                                $overlapCalendarDays = $overlapStart->diffInDays($overlapEnd) + 1;
                                $dailySalary = ($hourlyRate * ($contract->weekly_hours ?? 35) * 52 / 12) / 30;
                                $ijExpected = round($overlapCalendarDays * ($dailySalary * 0.5), 2);
                            }
                            
                            $ijssBrutesTotal += $ijExpected;
                            
                            // On déduit les IJSS Brutes du maintien brut pour ne pas payer de charges URSSAF dessus
                            $maintenanceGross -= $ijExpected;
                            // On empêche un maintien négatif
                            $maintenanceGross = max(0, $maintenanceGross);
                        }

                        $customBonuses[] = [
                            'label' => 'Maintien de salaire conventionnel',
                            'amount' => $maintenanceGross,
                            'is_taxable' => true,
                        ];
                    }
                } elseif ($absence->is_paid) {
                    $customBonuses[] = [
                        'label' => 'Indemnité ' . $absence->getType()->getLabel() . $labelSuffix,
                        'amount' => $deductionAmount,
                        'is_taxable' => true,
                    ];
                }
            }
        }

        // 2. Notes de Frais
        $expenseReportsAmount = \App\Models\RH\ExpenseReport::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('month', (int) $month)
            ->where('status', \App\Enums\RH\ExpenseReportStatus::PAID)
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
        // Utilisation du service de simulation pour le calcul brut -> net
        $simulator = new \App\Services\Paie\PayrollSimulatorService();
        $simulationDate = \Carbon\Carbon::createFromFormat('Y-m', $period)->endOfMonth()->startOfDay();
        
        $simulation = $simulator->simulateFromGross($grossSalary, $profile, $simulationDate, $ijssBrutesTotal);

        $totalEmployeeContributions = $simulation['total_employee_contributions'];
        $totalEmployerContributions = $simulation['total_employer_contributions'];
        
        // Enregistrement des lignes
        foreach ($simulation['lines'] as $line) {
            $payslip->lines()->create($line);
        }

        $exonerationEmployer = 939.74; // Réduction Fillon simulée
        $totalEmployerContributions -= $exonerationEmployer;

        // --- CALCULS DES NETS ---
        $netSocial = $simulation['net_social'];
        $taxableNet = $simulation['taxable_net'];

        // PAS (Prélèvement à la source)
        $pasRate = $employee->pas_rate ?? 0;
        $pasAmount = round($taxableNet * ($pasRate / 100), 2);

        $netPayable = round($netSocial - $pasAmount, 2);
        
        // Deduction IJSS Nettes: The employer advances the money, so the Net Pay includes the IJSS.
        // Wait, if the employer advances the money, the employee receives the Net Pay with the IJSS included!
        // So we DONT deduct IJSS Nettes from the Net Paid.
        // The CPAM pays the employer to reimburse them.
        // So the net payable is correct as is (it includes the IJSS Brutes from the simulator minus CSG).

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
