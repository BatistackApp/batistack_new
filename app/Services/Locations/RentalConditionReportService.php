<?php

namespace App\Services\Locations;

use App\Enums\Locations\RentalConditionReportType;
use App\Models\Locations\RentalConditionReport;
use App\Models\Locations\RentalContract;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Création et synchronisation des états des lieux de location entrante.
 * Horodatage côté serveur pour garantir l'intégrité des preuves (protection litiges).
 */
class RentalConditionReportService
{
    /**
     * Crée (ou renvoie) le rapport d'état des lieux de façon idempotente via $clientKey.
     *
     * @param  array{contract_id: int, type: string, comment?: string, latitude?: float, longitude?: float, signature?: string}  $payload
     */
    public function createFromSync(User $user, array $payload): ?RentalConditionReport
    {
        $contractId = (int) ($payload['contract_id'] ?? 0);
        $type = RentalConditionReportType::tryFrom($payload['type'] ?? '');
        $clientKey = (string) ($payload['client_key'] ?? '');

        if (! $type || ! $clientKey || ! $this->userManagesContract($user, $contractId)) {
            return null;
        }

        // Idempotence : renvoyer le rapport existant
        $existing = RentalConditionReport::where('client_key', $clientKey)->first();
        if ($existing) {
            return $existing;
        }

        $report = RentalConditionReport::create([
            'rental_contract_id' => $contractId,
            'type' => $type,
            'comment' => $payload['comment'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'captured_at' => now(),
            'client_key' => $clientKey,
        ]);

        if (! empty($payload['signature'])) {
            $report->sign($payload['signature']);
        }

        return $report;
    }

    /**
     * Rattache une photo base64 à un rapport d'état des lieux.
     */
    public function attachPhoto(RentalConditionReport $report, string $base64, ?string $fileName = null): void
    {
        try {
            $report->addMediaFromBase64($base64)
                ->usingFileName($fileName ?? 'photo_'.time().'.jpg')
                ->toMediaCollection('photos');
        } catch (\Throwable $e) {
            Log::error("Échec d'attachement de photo à l'état des lieux {$report->id}: ".$e->getMessage());
        }
    }

    /**
     * Vérifie que l'utilisateur (via sa fiche employé) gère le chantier du contrat.
     */
    public function userManagesContract(User $user, int $contractId): bool
    {
        $employeeId = $user->salarie?->id;

        if (! $employeeId) {
            return false;
        }

        return RentalContract::query()
            ->where('id', $contractId)
            ->whereHas('chantier', function ($q) use ($employeeId) {
                $q->where('manager_id', $employeeId)
                    ->orWhereHas('members', fn ($m) => $m->where('employees.id', $employeeId));
            })
            ->exists();
    }
}
