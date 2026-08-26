<?php

namespace App\Observers\RH;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Illuminate\Support\Facades\Storage;
use Log;

class AbscenceObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Abscence $absence): void
    {
        if (! $absence->employee_id) {
            throw new \Exception('Employee required');
        }
        if (! $absence->start_date) {
            throw new \Exception('Date required');
        }
        if (empty($absence->type)) {
            throw new \Exception('Absence type required');
        }

        // Automatisation de la subrogation
        if (in_array($absence->type, [AbsenceType::SICK_LEAVE, AbsenceType::WORK_ACCIDENT])) {
            // Par défaut, l'entreprise pratique la subrogation
            if (! isset($absence->requires_subrogation)) {
                $absence->requires_subrogation = true;
            }

            // Calcul estimatif des IJ (ex: 50% du brut journalier estimé)
            if ($absence->requires_subrogation && empty($absence->ij_expected)) {
                $employee = Employee::with('currentContract')->find($absence->employee_id);
                if ($employee && $employee->currentContract) {
                    $monthlyGross = $employee->currentContract->getSalary();
                    $dailyGross = $monthlyGross / 30; // Base calendaire Sécu
                    // Taux configurable (par défaut 50%)
                    $rate = config('rh.ij_base_rate', 0.50);
                    $durationDays = $absence->start_date->diffInDays($absence->end_date) + 1;
                    $absence->ij_expected = round($dailyGross * $rate * $durationDays, 2);
                }
            }
        }
    }

    public function created(Abscence $absence): void
    {
        Log::info('Absence created', ['id' => $absence->id, 'employee_id' => $absence->employee_id, 'type' => $absence->type, 'date' => $absence->start_date]);

        // Génération de l'attestation de salaire
        if (in_array($absence->type, [AbsenceType::SICK_LEAVE, AbsenceType::WORK_ACCIDENT])) {
            try {
                $pdfRelativePath = app(RHDocumentService::class)->generateAttestationSalaire($absence);
                $pdfAbsolutePath = Storage::disk('public')->path($pdfRelativePath);

                $absence->addMedia($pdfAbsolutePath)
                    ->toMediaCollection('attestations_salaire');
            } catch (\Exception $e) {
                Log::error("Impossible de générer l'attestation de salaire: ".$e->getMessage());
            }
        }
    }
}
