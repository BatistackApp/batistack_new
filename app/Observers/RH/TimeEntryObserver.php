<?php

namespace App\Observers\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\RH\TimeEntryStatusNotification;
use Illuminate\Support\Facades\Notification;
use Log;

class TimeEntryObserver
{
    /**
     * @throws \Exception
     */
    public function creating(TimeEntry $entry): void
    {
        if (!$entry->employee_id) {
            throw new \Exception('Employee required');
        }
        if (!$entry->date) {
            throw new \Exception('Date required');
        }
        if ($entry->hours < 0) {
            throw new \Exception('Hours must be positive');
        }
    }

    public function created(TimeEntry $timeEntry): void
    {
        Log::info('TimeEntry created', ['id' => $timeEntry->id, 'employee_id' => $timeEntry->employee_id, 'hours' => $timeEntry->hours]);
    }

    public function updated(TimeEntry $timeEntry): void
    {
        // Si le statut passe à 'submitted', on notifie les validateurs
        if ($timeEntry->isDirty('status') && $timeEntry->status === TimeEntryStatus::SUBMITTED) {
            $this->notifyValidators($timeEntry);
        }

        // Si le statut passe à 'approved' ou repasse en 'draft' (refus), on notifie l'employé
        if ($timeEntry->isDirty('status') && in_array($timeEntry->status, [TimeEntryStatus::APPROVED, TimeEntryStatus::DRAFT])) {
            $timeEntry->employee->notify(new TimeEntryStatusNotification($timeEntry));
            
            // [Nouveau] Synergie Chantiers : Vérification du budget global
            if ($timeEntry->status === TimeEntryStatus::APPROVED && $timeEntry->chantier_id) {
                $this->checkChantierBudget($timeEntry);
            }
        }

        if ($timeEntry->isDirty('hours')) {
            Log::info('TimeEntry hours updated', ['id' => $timeEntry->id, 'old_hours' => $timeEntry->getOriginal('hours'), 'new_hours' => $timeEntry->hours]);
        }
    }

    /**
     * Notifie les conducteurs de travaux pour validation.
     */
    protected function notifyValidators(TimeEntry $timeEntry): void
    {
        $validators = User::admin()->get(); // À filtrer par rôle 'Conducteur de Travaux'

        Notification::send($validators, new TimeEntryStatusNotification($timeEntry));
    }

    /**
     * Vérifie si l'ajout de ce pointage fait passer le chantier en marge négative.
     */
    protected function checkChantierBudget(TimeEntry $timeEntry): void
    {
        try {
            $analyticService = app(\App\Services\Chantiers\ChantierAnalyticService::class);
            $metrics = $analyticService->getPerformanceMetrics($timeEntry->chantier);
            $marginReal = $metrics['financials']['margin_real'];

            // Si la marge globale est négative
            if ($marginReal < 0) {
                // On calcule le coût du pointage qui vient d'être approuvé
                $hourlyRate = $timeEntry->employee->currentContract?->hourly_rate ?? 0;
                $entryCost = $timeEntry->hours * $hourlyRate;

                // Si sans ce pointage on était en positif ou à l'équilibre, c'est CET ajout qui a fait basculer la marge !
                // (marginReal = ancienne_marge - entryCost, donc ancienne_marge = marginReal + entryCost)
                $previousMargin = $marginReal + $entryCost;

                if ($previousMargin >= 0) {
                    // C'est le point de bascule : On notifie !
                    $manager = User::admin()->first(); // En situation réelle, on prendrait le manager lié au chantier
                    if ($manager) {
                        $manager->notify(new \App\Notifications\Chantiers\ChantierBudgetExceededNotification(
                            $timeEntry->chantier,
                            $marginReal
                        ));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification budgétaire RH/Chantiers', ['error' => $e->getMessage()]);
        }
    }
}
