<?php

namespace App\Services\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RHDocumentService extends DocumentService
{
    /**
     * Génère le contrat de travail d'un employé.
     */
    public function generateContract(Contract $contract): string
    {
        $contract->load(['employee']);

        $data = [
            'company' => Company::first(),
            'contract' => $contract,
            'employee' => $contract->employee,
            'title' => 'CONTRAT DE TRAVAIL - '.$contract->employee->full_name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.contract',
            $data,
            'contrat_'.$contract->employee->registration_number,
            'rh'
        );
    }

    /**
     * Génère un récapitulatif des habilitations et visites médicales (Passeport Sécurité).
     */
    public function generateSafetyPassport(Employee $employee): string
    {
        $employee->load(['qualifications', 'medicalVisits']);

        $publicUrl = route('public.safety-check', ['uuid' => $employee->uuid]);

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'title' => 'PASSEPORT SÉCURITÉ : '.$employee->full_name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'publicUrl' => $publicUrl,
        ];

        return $this->generate(
            'pdf.rh.safety_passport',
            $data,
            'passport_securite_'.$employee->id,
            'rh'
        );
    }

    /**
     * Génère la décharge de remise de matériel / EPI.
     */
    public function generateEquipmentHandover(Employee $employee, Collection $equipments): string
    {
        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'equipments' => $equipments,
            'title' => 'DÉCHARGE DE REMISE DE MATÉRIEL',
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.equipment_handover',
            $data,
            'decharge_materiel_'.$employee->id.'_'.now()->format('Ymd'),
            'rh'
        );
    }

    /**
     * Génère le relevé d'heures mensuel détaillé.
     */
    public function generateMonthlyTimesheet(Employee $employee, int $month, int $year): string
    {
        // Récupération des pointages approuvés du mois
        $entries = $employee->timeEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', TimeEntryStatus::APPROVED)
            ->orderBy('date')
            ->get();

        // Calcul des agrégats pour le bandeau de résumé du PDF
        $summary = [
            'total_hours' => $entries->sum('hours'),
            'total_travel_hours' => $entries->sum('travel_hours'),
            'gd_days' => $entries->where('is_grand_deplacement', true)->unique('date')->count(),
            // Simplification pour l'exemple, idéalement on boucle sur les semaines avec PayrollVariableService
            'overtime_25' => 0,
            'overtime_50' => 0,
        ];

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'entries' => $entries,
            'summary' => $summary,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year' => $year,
            'title' => "RELEVÉ D'ACTIVITÉ - {$employee->full_name}",
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.monthly_timesheet',
            $data,
            "releve_heures_{$employee->id}_{$year}_{$month}",
            'rh/timesheets',
            'landscape'
        );
    }

    public function generateFullRecord(Employee $employee): string
    {
        // Chargement complet des relations pour éviter les requêtes N+1
        $employee->load([
            'currentContract',
            'contracts',
            'equipements',
            'qualifications',
            'medicalVisits',
        ]);

        $publicUrl = route('public.safety-check', ['uuid' => $employee->uuid]);

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'publicUrl' => $publicUrl,
            'title' => 'DOSSIER INDIVIDUEL : '.$employee->full_name,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.employee_record',
            $data,
            'fiche_salarie_'.$employee->registration_number,
            'rh/records'
        );
    }

    /**
     * Génère un avertissement RH suite à une amende routière.
     */
    public function generateTrafficFineWarning(Employee $employee, \App\Models\Flottes\TrafficFine $fine): string
    {
        $fine->load(['vehicle']);

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'fine' => $fine,
            'title' => 'AVERTISSEMENT INFRACTION - ' . $employee->full_name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.traffic_fine_warning',
            $data,
            'avertissement_amende_'.$fine->reference.'_'.$employee->id,
            'rh/warnings'
        );
    }

    /**
     * Génère une fiche de paie pro forma (estimative).
     */
    public function generateProFormaPayslip(Employee $employee, int $month, int $year): string
    {
        $entries = $employee->timeEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', TimeEntryStatus::APPROVED)
            ->orderBy('date')
            ->get();

        $totalHours = $entries->sum('hours');
        $gdDays = $entries->where('is_grand_deplacement', true)->unique('date')->count();
        $contract = $employee->currentContract;
        $hourlyRate = $contract ? $contract->getHourlyRate() : 0;

        $contractHours = $contract ? $contract->weekly_hours : 35;
        $monthlyContractHours = round($contractHours * 4.33);

        $overtime25 = 0;
        $overtime50 = 0;
        if ($totalHours > $monthlyContractHours) {
            $diff = $totalHours - $monthlyContractHours;
            $overtime25 = min($diff, 34.6); // 8h par semaine * 4.33
            if ($diff > 34.6) {
                $overtime50 = $diff - 34.6;
            }
        }

        $gdAllowance = $gdDays * 96.00;

        $summary = [
            'total_hours' => $totalHours,
            'overtime_25' => $overtime25,
            'overtime_50' => $overtime50,
            'gd_days' => $gdDays,
            'gd_allowance' => $gdAllowance,
            'hourly_rate' => $hourlyRate,
            'gross_salary_estimate' => ($totalHours * $hourlyRate) + ($overtime25 * $hourlyRate * 0.25) + ($overtime50 * $hourlyRate * 0.5) + $gdAllowance,
        ];

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'contract' => $contract,
            'summary' => $summary,
            'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'year' => $year,
            'title' => "FICHE DE PAIE PRO FORMA - {$employee->full_name}",
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.payslip_proforma',
            $data,
            "paie_proforma_{$employee->id}_{$year}_{$month}",
            'rh/payslips'
        );
    }

    /**
     * Génère l'Attestation de Salaire (Arrêt Maladie / AT)
     */
    public function generateAttestationSalaire(\App\Models\RH\Abscence $absence): string
    {
        $absence->load(['employee.currentContract']);
        $employee = $absence->employee;
        
        // Simuler les 3 derniers salaires (en conditions réelles on lirait les tables de paie)
        $contract = $employee->currentContract;
        $monthlyGross = $contract ? $contract->getSalary() : 0;
        $hours = $contract ? $contract->getWeeklyHours() * 4.33 : 151.67;

        $referenceSalaries = [];
        for ($i = 3; $i >= 1; $i--) {
            $monthDate = $absence->start_date->copy()->subMonthsNoOverflow($i);
            $referenceSalaries[] = [
                'period' => $monthDate->translatedFormat('F Y'),
                'hours' => round($hours),
                'amount' => $monthlyGross,
            ];
        }

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'absence' => $absence,
            'reference_salaries' => $referenceSalaries,
            'title' => "ATTESTATION DE SALAIRE - {$employee->full_name}",
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.attestation_salaire',
            $data,
            "attestation_salaire_{$employee->id}_{$absence->start_date->format('Ymd')}",
            'rh/attestations'
        );
    }

    /**
     * Génère le bulletin d'affiliation (PRO BTP) pour l'Onboarding.
     */
    public function generateAffiliationMutuelle(\App\Models\RH\Employee $employee): string
    {
        $employee->load(['currentContract']);

        $data = [
            'company' => Company::first(),
            'employee' => $employee,
            'contract' => $employee->currentContract,
            'title' => "BULLETIN AFFILIATION - {$employee->full_name}",
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.rh.affiliation_mutuelle',
            $data,
            "affiliation_probtp_{$employee->id}_{$employee->registration_number}",
            'rh/onboarding'
        );
    }
}
