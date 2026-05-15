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
}
