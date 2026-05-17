<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleConditionReport;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VehicleConditionService
{
    /**
     * Soumet un état des lieux complet (Check-in ou Check-out).
     *
     * * @param array $photos Doit contenir obligatoirement les 5 fichiers d'images (front, back, left, right, dashboard)
     *
     * @throws Exception
     */
    public function submitReport(
        VehicleAssignment $assignment,
        ConditionReportType $type, // 'check-in' ou 'check-out'
        float $odometer,
        int $fuelLevel,
        string $driverPin, // PIN de sécurité à 4 chiffres déclaré dans le dossier RH de l'employé
        array $photos,
        ?string $comments = null
    ): VehicleConditionReport {

        // 1. Validation de la garde de l'affectation
        if ($assignment->status !== AssignmentStatus::ACTIVE) {
            throw new Exception("L'affectation sélectionnée n'est pas active.");
        }

        // 2. Validation de cohérence de l'odomètre
        if ($type === ConditionReportType::CHECK_IN && $odometer < $assignment->start_odometer) {
            throw new Exception("L'odomètre de départ saisi ({$odometer} km) ne peut pas être inférieur au kilométrage d'origine du véhicule ({$assignment->start_odometer} km).");
        }

        if ($type === ConditionReportType::CHECK_OUT && $odometer < $assignment->start_odometer) {
            throw new Exception("L'odomètre de retour saisi ({$odometer} km) ne peut pas être inférieur au kilométrage de départ du trajet ({$assignment->start_odometer} km).");
        }

        // 3. Validation de la présence absolue des 5 visuels requis (4 faces + tableau de bord)
        $requiredKeys = ['front', 'back', 'left', 'right', 'dashboard'];
        foreach ($requiredKeys as $key) {
            if (empty($photos[$key])) {
                throw new Exception("La photo de la zone '{$key}' est obligatoire pour valider l'état des lieux.");
            }
        }

        // 4. Vérification d'engagement juridique via Code PIN Secret du salarié
        $employee = $assignment->employee;

        // Pour les besoins du test/démo, on suppose que le PIN est stocké haché sur le profil de l'employé (ou via un champ de mot de passe secondaire)
        // Ici nous validons le PIN fourni face au pin_hash de l'employé (ex: par défaut '1234')
        $employeePinHash = $employee->pin_hash ?? Hash::make('1234');

        if (! Hash::check($driverPin, $employeePinHash)) {
            throw new Exception("Le code PIN secret saisi est invalide. Validation de l'état des lieux rejetée.");
        }

        // 5. Scellé numérique de l'état du véhicule pour éviter toute falsification ultérieure
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
            // Enregistrement de l'état des lieux en base de données
            $report = VehicleConditionReport::create([
                'vehicle_assignment_id' => $assignment->id,
                'type' => $type,
                'odometer' => $odometer,
                'fuel_level' => $fuelLevel,
                'signature_checksum' => $signatureChecksum,
                'signed_at' => now(),
                'comment' => $comments,
            ]);

            // Attachement des médias aux collections Spatie correspondantes
            $report->addMedia($photos['front'])->toMediaCollection('photo_front');
            $report->addMedia($photos['back'])->toMediaCollection('photo_back');
            $report->addMedia($photos['left'])->toMediaCollection('photo_left');
            $report->addMedia($photos['right'])->toMediaCollection('photo_right');
            $report->addMedia($photos['dashboard'])->toMediaCollection('photo_dashboard');

            // Si c'est un état de retour (check-out), on clôture proprement l'affectation du véhicule
            if ($type === ConditionReportType::CHECK_OUT) {
                // Appel du service d'affectation pour libérer le véhicule
                app(VehicleAssignmentService::class)->endAssignment($assignment, now(), $odometer);
            }

            return $report;
        });
    }
}
