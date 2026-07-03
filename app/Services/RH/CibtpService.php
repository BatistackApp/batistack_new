<?php

namespace App\Services\RH;

use App\Models\Chantiers\WeatherAlert;
use App\Models\RH\CibtpDeclaration;
use Carbon\Carbon;

class CibtpService
{
    /**
     * Generates a draft CIBTP declaration from a WeatherAlert.
     */
    public function generateDraftFromAlert(WeatherAlert $alert): ?CibtpDeclaration
    {
        // Don't create multiple declarations for the same alert
        if (CibtpDeclaration::where('weather_alert_id', $alert->id)->exists()) {
            return null;
        }

        $chantier = $alert->chantier;
        
        // Estimate the number of employees affected
        // In a real application, you might check Chantier->members() or specific schedules
        $affectedEmployeesCount = $chantier->members()->count() ?: 1; 

        // Assuming 7 hours lost per affected employee for a full day alert
        $estimatedLostHours = $affectedEmployeesCount * 7.0;

        return CibtpDeclaration::create([
            'chantier_id' => $chantier->id,
            'weather_alert_id' => $alert->id,
            'date' => $alert->started_at->toDateString(),
            'status' => 'draft',
            'total_lost_hours' => $estimatedLostHours,
        ]);
    }

    /**
     * Génère l'export CSV de la Déclaration Nominative Annuelle (DNA)
     * Période de référence : 1er Avril N-1 au 31 Mars N
     */
    public function generateDNA(int $year): string
    {
        $startDate = Carbon::create($year - 1, 4, 1)->startOfDay();
        $endDate = Carbon::create($year, 3, 31)->endOfDay();

        $employees = \App\Models\RH\Employee::with(['currentContract'])->get();

        $csvData = [];
        // En-têtes
        $csvData[] = ['Matricule', 'Nom', 'Prénom', 'NIR', 'Heures Travaillées', 'Salaire Brut Période'];

        foreach ($employees as $employee) {
            $hours = \App\Models\RH\TimeEntry::where('employee_id', $employee->id)
                ->where('status', \App\Enums\RH\TimeEntryStatus::APPROVED)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('hours');

            $contract = $employee->currentContract;
            $hourlyRate = $contract ? $contract->getHourlyRate() : 0;
            $grossSalary = round($hours * $hourlyRate, 2);

            if ($hours > 0 || $contract) {
                $csvData[] = [
                    $employee->registration_number,
                    $employee->last_name,
                    $employee->first_name,
                    $employee->social_security_number ?? '',
                    round($hours, 2),
                    $grossSalary
                ];
            }
        }

        return $this->arrayToCsv($csvData);
    }

    /**
     * Génère l'export CSV des Demandes De Congés (DDC)
     * @param \Illuminate\Database\Eloquent\Collection $absences
     */
    public function generateDDC($absences): string
    {
        $csvData = [];
        // En-têtes
        $csvData[] = ['Matricule', 'Nom', 'Prénom', 'Date Début', 'Date Fin', 'Dernier Jour Travaillé', 'Type'];

        foreach ($absences as $absence) {
            $employee = $absence->employee;
            $lastWorkedDay = $absence->start_date->copy()->subDay()->format('d/m/Y');
            
            $csvData[] = [
                $employee->registration_number,
                $employee->last_name,
                $employee->first_name,
                $absence->start_date->format('d/m/Y'),
                $absence->end_date->format('d/m/Y'),
                $lastWorkedDay,
                $absence->type->value
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    /**
     * Convertit un tableau 2D en chaîne CSV
     */
    private function arrayToCsv(array $data): string
    {
        $out = fopen('php://temp', 'r+');
        // Ajout du BOM UTF-8 pour Excel
        fputs($out, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
        foreach ($data as $row) {
            fputcsv($out, $row, ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }
}
