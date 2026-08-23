<?php

namespace App\Services\Paie;

use App\Enums\Paie\DsnStatus;
use App\Enums\Paie\DsnSubmissionStatus;
use App\Models\Paie\DsnSubmission;
use App\Models\Paie\DsnSubmissionLine;
use App\Models\Paie\Payslip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DsnExportService
{
    /**
     * Generate a simplified CSV export for DADS/DSN (legacy format for accountant).
     */
    public function generateCsv(Collection $payslips): string
    {
        $filename = 'export_dads_dsn_' . Carbon::now()->format('Y_m_d_His') . '.csv';

        $csvData = [];
        $csvData[] = [
            'Matricule',
            'Nom',
            'Prenom',
            'N_Secu',
            'Date_Naissance',
            'Periode',
            'SIRET_Etablissement',
            'Heures_Base',
            'Heures_Sup',
            'Salaire_Brut',
            'Net_Social',
            'Net_Imposable',
            'Taux_PAS',
            'Montant_PAS',
            'Net_Paye',
            'Cout_Global_Employeur',
        ];

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $company = $employee->company ?? null;

            $csvData[] = [
                $employee->registration_number ?? $employee->id,
                $employee->last_name,
                $employee->first_name,
                $employee->social_security_number,
                $employee->birth_date?->format('d/m/Y'),
                $payslip->period,
                $company->siret ?? '',
                $payslip->base_hours,
                $payslip->overtime_hours,
                $payslip->gross_salary,
                $payslip->net_social,
                $payslip->taxable_net,
                $payslip->pas_rate,
                $payslip->pas_amount,
                $payslip->net_paid,
                $payslip->employer_cost,
            ];
        }

        $output = fopen('php://temp', 'r+');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

    /**
     * Generate CSV and create a DsnSubmission record for an expert accountant export.
     */
    public function generateForAccountant(Collection $payslips, string $period, ?int $companyId, int $userId): DsnSubmission
    {
        $path = $this->generateCsv($payslips);

        return DB::transaction(function () use ($payslips, $period, $companyId, $userId, $path) {
            $submission = DsnSubmission::create([
                'company_id' => $companyId,
                'period' => $period,
                'status' => DsnSubmissionStatus::EXPORTED,
                'export_type' => 'csv_expert',
                'exported_at' => now(),
                'exported_file_path' => $path,
                'payslips_count' => $payslips->count(),
                'total_gross' => $payslips->sum('gross_salary'),
                'total_net' => $payslips->sum('net_payable'),
                'total_employer_cost' => $payslips->sum('employer_cost'),
                'created_by' => $userId,
            ]);

            foreach ($payslips as $payslip) {
                DsnSubmissionLine::create([
                    'dsn_submission_id' => $submission->id,
                    'payslip_id' => $payslip->id,
                    'status' => 'exported',
                ]);

                $payslip->update([
                    'dsn_status' => DsnStatus::EXPORTED->value,
                    'dsn_exported_at' => now(),
                ]);
            }

            return $submission;
        });
    }

    /**
     * Get export summary with validation checks.
     */
    public function getExportSummary(Collection $payslips): array
    {
        $warnings = [];

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;

            if (empty($employee->social_security_number)) {
                $warnings[] = "{$employee->last_name} {$employee->first_name} : N° Sécurité Sociale manquant";
            }
            if (empty($employee->birth_date)) {
                $warnings[] = "{$employee->last_name} {$employee->first_name} : Date de naissance manquante";
            }
        }

        return [
            'count' => $payslips->count(),
            'total_gross' => $payslips->sum('gross_salary'),
            'total_net' => $payslips->sum('net_payable'),
            'total_employer_cost' => $payslips->sum('employer_cost'),
            'warnings' => $warnings,
            'has_warnings' => count($warnings) > 0,
        ];
    }

    /**
     * Mark payslips as ready for DSN export.
     */
    public function markAsReady(Collection $payslips): void
    {
        $payslips->each->update([
            'dsn_status' => DsnStatus::READY->value,
        ]);
    }
}
