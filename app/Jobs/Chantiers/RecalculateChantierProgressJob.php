<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Notifications\Chantiers\ChantierBudgetAlertNotification;
use App\Services\Chantiers\ChantierAnalyticService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateChantierProgressJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Chantier $chantier) {}

    public function uniqueId(): string
    {
        return "chantier_progress_{$this->chantier->id}";
    }

    public function handle(ChantierAnalyticService $service): void
    {
        // 1. On récupère les indicateurs frais via le service analytique
        $metrics = $service->getPerformanceMetrics($this->chantier);

        // 2. Surveillance du budget heures : Alerte à 90%
        if ($metrics['hours']['percent'] >= 90 && $this->chantier->manager) {
            $this->chantier->manager->notify(new ChantierBudgetAlertNotification($this->chantier, $metrics));
        }

        // On peut ici logger le résultat pour le suivi de performance des queues
        Log::info("Recalcul de l'avancement effectué pour le chantier {$this->chantier->reference}.");
    }
}
