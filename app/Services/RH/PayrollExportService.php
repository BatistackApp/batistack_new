<?php

namespace App\Services\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class PayrollExportService
{
    /**
     * Generate a CSV string containing payroll data for a specific month and year.
     *
     * @param int $month
     * @param int $year
     * @return string
     */
    public function generateCsv(int $month, int $year): string
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all active employees with their approved time entries and absences in the given period
        $employees = Employee::active()
            ->with([
                'timeEntries' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                        ->where('status', TimeEntryStatus::APPROVED);
                },
                'absences' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate]);
                    });
                }
            ])->get();

        $csvData = [];
        $csvData[] = ['ID Employé', 'Matricule', 'Nom', 'Prénom', 'Heures Normales', 'Jours Grands Déplacements', 'Heures Trajet', 'Jours Absences'];

        foreach ($employees as $employee) {
            $totalHours = $employee->timeEntries->sum('hours');
            $totalTravel = $employee->timeEntries->sum('travel_hours');
            $gdDays = $employee->timeEntries->where('is_grand_deplacement', true)->count();
            
            // Calculate absence days within the period (approximative for full days)
            $absenceDays = 0;
            foreach ($employee->absences as $absence) {
                // If absence starts before the month, cap it at start of month
                $absStart = $absence->start_date->max($startDate);
                // If absence ends after the month, cap it at end of month
                $absEnd = $absence->end_date->min($endDate);
                
                if ($absStart <= $absEnd) {
                    // diffInDays doesn't count the same day, so add 1
                    $absenceDays += $absStart->diffInDays($absEnd) + 1;
                }
            }

            $csvData[] = [
                $employee->id,
                $employee->registration_number ?? '',
                $employee->last_name,
                $employee->first_name,
                $totalHours,
                $gdDays,
                $totalTravel,
                $absenceDays,
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    /**
     * Download the generated CSV.
     */
    public function downloadCsv(int $month, int $year)
    {
        $csvString = $this->generateCsv($month, $year);
        $filename = "export_paie_{$year}_{$month}.csv";

        return Response::make($csvString, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
