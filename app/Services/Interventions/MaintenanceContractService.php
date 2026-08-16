<?php

namespace App\Services\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Interventions\MaintenanceContractReminder;
use App\Notifications\Interventions\MaintenanceContractReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MaintenanceContractService
{
    /**
     * Génère les interventions des contrats actifs dont l'échéance est atteinte.
     */
    public function generateDueInterventions(?CarbonInterface $date = null): int
    {
        $date = $date ?: now();

        $contracts = MaintenanceContract::query()
            ->where('status', MaintenanceContractStatus::ACTIVE)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $date->toDateString())
            ->get();

        $count = 0;

        foreach ($contracts as $contract) {
            try {
                if ($this->generateForContract($contract, $date)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::warning('Échec de la génération d\'une intervention de maintenance', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Génère une intervention pour un contrat et avance sa prochaine échéance.
     */
    public function generateForContract(
        MaintenanceContract $contract,
        ?CarbonInterface $date = null,
        bool $force = false,
    ): bool {
        $date = $date ?: now();

        return DB::transaction(function () use ($contract, $date, $force) {
            $locked = MaintenanceContract::whereKey($contract->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return false;
            }

            if ($locked->status !== MaintenanceContractStatus::ACTIVE) {
                return false;
            }

            if (! $force && (is_null($locked->next_due_date) || $locked->next_due_date->gt($date))) {
                return false;
            }

            $intervention = $locked->interventions()->create([
                'third_party_id' => $locked->third_party_id,
                'client_equipment_id' => $locked->client_equipment_id,
                'chantier_id' => $locked->chantier_id,
                'type' => InterventionType::FORFAIT,
                'status' => InterventionStatus::PLANIFIEE,
                'description' => $locked->description
                    ?: "Maintenance préventive {$locked->frequency->getLabel()} - {$locked->name}",
                'scheduled_at' => $locked->next_due_date?->startOfDay() ?? now(),
                'flat_rate_price' => $locked->flat_rate_price,
            ]);

            $nextDue = $this->computeNextDueDate($locked, $locked->next_due_date ?? now());

            $locked->update([
                'last_generated_at' => now(),
                'next_due_date' => $nextDue?->toDateString(),
                'status' => $nextDue === null
                    ? MaintenanceContractStatus::COMPLETED
                    : $locked->status,
            ]);

            return $intervention->exists;
        });
    }

    /**
     * Calcule la prochaine échéance selon la fréquence du contrat.
     * Retourne null lorsque la date dépasse la fin du contrat.
     */
    public function computeNextDueDate(MaintenanceContract $contract, CarbonInterface $from): ?CarbonInterface
    {
        $next = match ($contract->frequency) {
            MaintenanceContractFrequency::MONTHLY => $from->copy()->addMonth(),
            MaintenanceContractFrequency::QUARTERLY => $from->copy()->addMonths(3),
            MaintenanceContractFrequency::SEMI_ANNUAL => $from->copy()->addMonths(6),
            MaintenanceContractFrequency::ANNUAL => $from->copy()->addYear(),
        };

        if ($contract->end_date && $next->gt($contract->end_date)) {
            return null;
        }

        return $next;
    }

    /**
     * Envoie les rappels d'échéance (J-30 / J-15 / J-7) aux clients concernés.
     * Chaque rappel n'est envoyé qu'une seule fois par échéance.
     */
    public function notifyUpcoming(?CarbonInterface $date = null): int
    {
        $date = $date ?: now();
        $daysBefore = config('interventions.maintenance_reminder_days_before', [30, 15, 7]);

        $contracts = MaintenanceContract::query()
            ->where('status', MaintenanceContractStatus::ACTIVE)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $date->copy()->addDays(max($daysBefore))->toDateString())
            ->with(['thirdParty'])
            ->get();

        $count = 0;

        foreach ($contracts as $contract) {
            foreach ($daysBefore as $days) {
                if ($this->sendReminderIfDue($contract, $date, (int) $days)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function sendReminderIfDue(MaintenanceContract $contract, CarbonInterface $date, int $daysBefore): bool
    {
        $due = $contract->next_due_date;

        if ($due->copy()->subDays($daysBefore)->gt($date)) {
            return false;
        }

        $recipient = $this->resolveRecipient($contract);

        if (blank($recipient)) {
            return false;
        }

        $reminder = MaintenanceContractReminder::firstOrCreate([
            'contract_id' => $contract->id,
            'due_date' => $due,
            'days_before' => $daysBefore,
        ], [
            'sent_at' => now(),
        ]);

        if (! $reminder->wasRecentlyCreated) {
            return false;
        }

        try {
            Notification::route('mail', $recipient)
                ->notify(new MaintenanceContractReminderNotification($contract));
        } catch (\Throwable $e) {
            $reminder->delete();

            throw $e;
        }

        return true;
    }

    protected function resolveRecipient(MaintenanceContract $contract): ?string
    {
        $contact = $contract->thirdParty?->getPrimaryContact();

        if ($contact?->email) {
            return $contact->email;
        }

        return $contract->thirdParty?->email;
    }
}
