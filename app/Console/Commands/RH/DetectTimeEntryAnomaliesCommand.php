<?php

namespace App\Console\Commands\RH;

use App\Services\RH\TimeEntryAnomalyDetectorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DetectTimeEntryAnomaliesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rh:detect-time-anomalies {--date= : La date à vérifier (Y-m-d)} {--tolerance=1 : La tolérance en heures}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Détecte les anomalies de pointage en croisant avec le module Flottes';

    /**
     * Execute the console command.
     */
    public function handle(TimeEntryAnomalyDetectorService $service)
    {
        $dateStr = $this->option('date');
        $date = $dateStr ? Carbon::parse($dateStr) : Carbon::yesterday();
        $tolerance = (float) $this->option('tolerance');

        $this->info("Analyse des pointages du {$date->toDateString()} avec une tolérance de {$tolerance}h...");

        $count = $service->detectForDate($date, $tolerance);

        if ($count > 0) {
            $this->warn("{$count} anomalie(s) détectée(s) pour la journée du {$date->toDateString()}.");
        } else {
            $this->info("Aucune anomalie détectée pour la journée du {$date->toDateString()}.");
        }
    }
}
