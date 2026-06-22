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
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FleetExpenseService
{
    /**
     * Enregistre un nouveau frais de route.
     * @throws \Throwable
     */
    public function registerExpense(
        Vehicle $vehicle,
        string $type,
        float $amountHt,
        VatRate $vatRate,
        CarbonInterface $spentAt,
        ?string $merchantName = null,
        ?string $reference = null,
        ?string $description = null
    ): FleetExpense {
        return DB::transaction(function () use ($vehicle, $type, $amountHt, $vatRate, $spentAt, $merchantName, $reference, $description) {

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

            if ($employeeId) {
                $hasWorkEntry = TimeEntry::where('employee_id', $employeeId)
                    ->whereDate('date', $spentAt->toDateString())
                    ->whereIn('status', [TimeEntryStatus::SUBMITTED, TimeEntryStatus::APPROVED])
                    ->exists();

                if ($spentAt->isSunday()) {
                    $isSuspicious = true;
                    $suspicionReason = "Frais de route enregistrés un dimanche pour {$vehicle->reference}.";
                } elseif (! $hasWorkEntry) {
                    $driver = $assignment->employee;
                    $isSuspicious = true;
                    $suspicionReason = 'Frais enregistrés le '.$spentAt->format('d/m/Y')." mais aucun pointage RH pour {$driver->getFullName()}.";
                }
            } else {
                $isSuspicious = true;
                $suspicionReason = 'Frais de route sans conducteur affecté (Badge ou véhicule volé/emprunté).';
            }

            $amountTtc = $amountHt * (1 + ($vatRate->rate / 100));

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

            if ($isSuspicious) {
                $managers = User::where('is_admin', true)->get();
                Notification::send($managers, new FleetExpenseAnomalyNotification($expense));
            }

            if ($chantierId) {
                logger()->info("Imputation : Frais #{$expense->id} ({$expense->amount_ttc}€ TTC) imputé au chantier #{$chantierId}.");
            }

            return $expense;
        });
    }

    /**
     * Obtient les dépenses par type sur une période.
     */
    public function getExpensesByType(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): array
    {
        $expenses = $vehicle->expenses()
            ->whereBetween('spent_at', [$from, $to])
            ->get()
            ->groupBy('type');

        $summary = [];
        foreach ($expenses as $type => $items) {
            $summary[$type] = [
                'count' => $items->count(),
                'total_ht' => (float) $items->sum('amount_ht'),
                'total_ttc' => (float) $items->sum('amount_ttc'),
            ];
        }

        return $summary;
    }

    /**
     * Obtient les dépenses suspectes.
     */
    public function getSuspiciousExpenses(Vehicle $vehicle): Collection
    {
        return $vehicle->expenses()
            ->where('is_suspicious', true)
            ->orderByDesc('spent_at')
            ->get();
    }

    /**
     * Total des dépenses suspectes.
     */
    public function getSuspiciousExpensesTotal(Vehicle $vehicle): float
    {
        return (float) $this->getSuspiciousExpenses($vehicle)->sum('amount_ttc');
    }

    /**
     * Obtient les dépenses par chantier.
     */
    public function getExpensesByChantier(Vehicle $vehicle): array
    {
        return $vehicle->expenses()
            ->with('chantier')
            ->get()
            ->groupBy('chantier_id')
            ->map(function ($group) {
                return [
                    'chantier_id' => $group->first()->chantier_id,
                    'chantier_name' => $group->first()->chantier?->reference ?? 'Non alloué',
                    'count' => $group->count(),
                    'total_ttc' => (float) $group->sum('amount_ttc'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Calcule les dépenses moyennes par jour d'utilisation.
     */
    public function getAverageDailyExpense(Vehicle $vehicle, Carbon $from, Carbon $to): float
    {
        $totalExpenses = (float) $vehicle->expenses()
            ->whereBetween('spent_at', [$from, $to])
            ->sum('amount_ttc');

        $days = $from->diffInDays($to);

        return $days > 0 ? round($totalExpenses / $days, 2) : 0;
    }
}
