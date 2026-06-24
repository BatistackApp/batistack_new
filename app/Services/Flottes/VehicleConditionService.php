<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleConditionReport;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VehicleConditionService
{
    public function __construct(
        protected VehicleAssignmentService $assignmentService
    ) {}

    /**
     * Soumet un état des lieux complet (Check-in ou Check-out).
     *
     * @throws Exception
     * @throws \Throwable
     */
    public function submitReport(
        VehicleAssignment $assignment,
        ConditionReportType $type,
        float $odometer,
        int $fuelLevel,
        string $driverPin,
        array $photos,
        ?string $comments = null
    ): VehicleConditionReport {

        if ($assignment->status !== AssignmentStatus::ACTIVE) {
            throw new Exception("L'affectation n'est pas active.");
        }

        if ($type === ConditionReportType::CHECK_IN && $odometer < $assignment->start_odometer) {
            throw new Exception("L'odomètre de départ ne peut pas être inférieur au kilométrage d'origine.");
        }

        if ($type === ConditionReportType::CHECK_OUT && $odometer < $assignment->start_odometer) {
            throw new Exception("L'odomètre de retour ne peut pas être inférieur au kilométrage de départ.");
        }

        $requiredKeys = ['front', 'back', 'left', 'right', 'dashboard'];
        foreach ($requiredKeys as $key) {
            if (empty($photos[$key])) {
                throw new Exception("La photo de la zone '{$key}' est obligatoire.");
            }
        }

        $employee = $assignment->employee;
        $employeePinHash = $employee->pin_hash;

        if (! Hash::check($driverPin, $employeePinHash)) {
            throw new Exception('Le code PIN saisi est invalide.');
        }

        $payloadToSign = json_encode([
            'assignment_id' => $assignment->id,
            'driver_id' => $employee->id,
            'type' => $type,
            'odometer' => $odometer,
            'fuel_level' => $fuelLevel,
            'timestamp' => now()->toIso8601String(),
        ]);
        $signatureChecksum = hash_hmac('sha256', $payloadToSign, config('app.key'));

        return DB::transaction(function () use ($assignment, $type, $odometer, $fuelLevel, $signatureChecksum, $photos, $comments) {
            $report = VehicleConditionReport::create([
                'vehicle_assignment_id' => $assignment->id,
                'type' => $type,
                'odometer' => $odometer,
                'fuel_level' => $fuelLevel,
                'signature_checksum' => $signatureChecksum,
                'signed_at' => now(),
                'comment' => $comments,
            ]);

            $report->addMedia($photos['front'])->toMediaCollection('photo_front');
            $report->addMedia($photos['back'])->toMediaCollection('photo_back');
            $report->addMedia($photos['left'])->toMediaCollection('photo_left');
            $report->addMedia($photos['right'])->toMediaCollection('photo_right');
            $report->addMedia($photos['dashboard'])->toMediaCollection('photo_dashboard');

            if ($type === ConditionReportType::CHECK_OUT) {
                $this->assignmentService->endAssignment($assignment, now(), $odometer);
            }

            return $report;
        });
    }

    /**
     * Obtient tous les états des lieux pour une affectation.
     */
    public function getReportsForAssignment(VehicleAssignment $assignment): Collection
    {
        return VehicleConditionReport::byAssignment($assignment->id)
            ->orderByDesc('signed_at')
            ->get();
    }

    /**
     * Obtient le dernier état des lieux.
     */
    public function getLastReport(VehicleAssignment $assignment): ?VehicleConditionReport
    {
        return VehicleConditionReport::byAssignment($assignment->id)
            ->orderByDesc('signed_at')
            ->first();
    }

    /**
     * Vérifie si tous les contrôles requis sont faits.
     */
    public function isAssignmentComplete(VehicleAssignment $assignment): bool
    {
        $checkIn = VehicleConditionReport::byAssignment($assignment->id)
            ->where('type', ConditionReportType::CHECK_IN)
            ->exists();

        $checkOut = VehicleConditionReport::byAssignment($assignment->id)
            ->where('type', ConditionReportType::CHECK_OUT)
            ->exists();

        return $checkIn && $checkOut;
    }

    /**
     * Obtient les photos manquantes d'un rapport.
     */
    public function getMissingPhotos(VehicleConditionReport $report): array
    {
        return $report->getMissingPhotos();
    }
}
