<?php

namespace App\Services\RH;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Core\Signature;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\Core\SignatureService;
use Exception;

class TimeSignatureService
{
    public function __construct(protected SignatureService $signatureService) {}

    /**
     * Prépare et demande la signature du relevé mensuel.
     */
    public function requestMonthlySignature(Employee $employee, int $month, int $year): Signature
    {
        // 1. Vérifier si une signature est déjà en cours ou validée pour cette période
        $existing = Signature::where('signable_type', Employee::class)
            ->where('signable_id', $employee->id)
            ->whereJsonContains('metadata->month', $month)
            ->whereJsonContains('metadata->year', $year)
            ->first();

        if ($existing && $existing->status !== SignatureStatus::REFUSED) {
            throw new Exception('Une demande de signature existe déjà pour cette période.');
        }

        // 2. Récupérer et agréger les pointages approuvés du mois
        $entries = TimeEntry::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', TimeEntryStatus::APPROVED)
            ->get();

        if ($entries->isEmpty()) {
            throw new Exception('Aucun pointage approuvé trouvé pour ce mois.');
        }

        $summary = [
            'total_hours' => (float) $entries->sum('hours'),
            'normal_hours' => (float) $entries->where('type', TimeEntryType::NORMAL)->sum('hours'),
            'overtime_25' => (float) $entries->where('type', TimeEntryType::OVERTIME_25)->sum('hours'),
            'overtime_50' => (float) $entries->where('type', TimeEntryType::OVERTIME_50)->sum('hours'),
            'days_worked' => $entries->unique('date')->count(),
            'gd_days' => $entries->where('is_grand_deplacement', true)->unique('date')->count(),
        ];

        // 3. Créer un "Snapshot" textuel pour le certificat de signature
        $snapshot = "Récapitulatif d'activité - {$month}/{$year}\n";
        $snapshot .= "Salarié : {$employee->full_name}\n";
        $snapshot .= "Total Heures : {$summary['total_hours']}h\n";
        $snapshot .= "Dont 25% : {$summary['overtime_25']}h | 50% : {$summary['overtime_50']}h\n";
        $snapshot .= "Jours en Grand Déplacement : {$summary['gd_days']}";

        // 4. Utiliser le service Core pour initier la signature
        return $this->signatureService->requestSignature($employee, SignatureType::AUTOGRAPH);
    }
}
