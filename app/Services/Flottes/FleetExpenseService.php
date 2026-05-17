<?php

namespace App\Services\Flottes;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\VatRate;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\Flottes\FleetExpenseAnomalyNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FleetExpenseService
{
    /**
     * Enregistre un nouveau frais de route et l'audite automatiquement face aux plannings RH.
     */
    public function registerExpense(
        Vehicle $vehicle,
        string $type, // 'toll' ou 'parking'
        float $amountHt,
        VatRate $vatRate,
        Carbon $spentAt,
        ?string $merchantName = null,
        ?string $reference = null,
        ?string $description = null
    ): FleetExpense {
        return DB::transaction(function () use ($vehicle, $type, $amountHt, $vatRate, $spentAt, $merchantName, $reference, $description) {

            // 1. Résolution du chauffeur actif au moment de la transaction
            $assignment = VehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('started_at', '<=', $spentAt)
                ->where(function ($q) use ($spentAt) {
                    $q->where('ended_at', '>=', $spentAt)
                        ->orWhereNull('ended_at');
                })
                ->first();

            $employeeId = $assignment?->employee_id;
            $chantierId = $assignment?->chantier_id;

            $isSuspicious = false;
            $suspicionReason = null;

            // 2. Moteur anti-fraude de croisement avec les plannings et pointages RH
            if ($employeeId) {
                // Recherche d'un pointage d'heures de travail approuvé/soumis ce jour-là
                $hasWorkEntry = TimeEntry::where('employee_id', $employeeId)
                    ->whereDate('date', $spentAt->toDateString())
                    ->whereIn('status', [TimeEntryStatus::SUBMITTED, TimeEntryStatus::APPROVED])
                    ->exists();

                if ($spentAt->isSunday()) {
                    $isSuspicious = true;
                    $suspicionReason = "Frais de route enregistrés un dimanche pour l'actif {$vehicle->reference}.";
                } elseif (! $hasWorkEntry) {
                    $driver = $assignment->employee;
                    $isSuspicious = true;
                    $suspicionReason = 'Frais enregistrés le '.$spentAt->format('d/m/Y')." mais aucun pointage RH d'activité n'est saisi pour {$driver->full_name} ce jour-là.";
                }
            } else {
                // Utilisation du véhicule (ou badge péage) hors affectation active
                $isSuspicious = true;
                $suspicionReason = 'Frais de route constatés sur le véhicule sans aucun conducteur affecté à cette date/heure (Badge ou véhicule volé/emprunté).';
            }

            // 3. Calcul du TTC
            $amountTtc = $amountHt * (1 + ($vatRate->rate / 100));

            // 4. Enregistrement en base de données
            $expense = FleetExpense::create([
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employeeId,
                'chantier_id' => $chantierId,
                'type' => $type,
                'reference' => $reference,
                'amount_ht' => $amountHt,
                'vat_rate_id' => $vatRate->id,
                'amount_ttc' => round($amountTtc, 2),
                'merchant_name' => $merchantName,
                'description' => $description,
                'spent_at' => $spentAt,
                'is_suspicious' => $isSuspicious,
                'suspicion_reason' => $suspicionReason,
            ]);

            // 5. Alerte immédiate des managers en cas de suspicion
            if ($isSuspicious) {
                $managers = User::all(); // À filtrer par rôle approprié en production
                Notification::send($managers, new FleetExpenseAnomalyNotification($expense));
            }

            // 6. Imputation analytique silencieuse vers les logs de chantiers
            if ($chantierId) {
                logger()->info("Imputation Directe : Le frais de route #{$expense->id} ({$expense->amount_ttc} € TTC) a été imputé analytiquement au chantier #{$chantierId}.");
            }

            return $expense;
        });
    }
}
