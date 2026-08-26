<?php

namespace App\Console\Commands;

use App\Models\Chantiers\Chantier;
use App\Models\User;
use App\Notifications\Chantiers\WeatherAlertNotification;
use App\Notifications\RH\CibtpDeclarationNeededNotification;
use App\Services\Core\WeatherAlertService;
use App\Services\RH\CibtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckWeatherAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la météo pour tous les chantiers en cours et génère les alertes et brouillons CIBTP';

    /**
     * Execute the console command.
     */
    public function handle(WeatherAlertService $weatherService, CibtpService $cibtpService): void
    {
        $chantiers = Chantier::where('status', 'in_progress')->get();

        foreach ($chantiers as $chantier) {
            $alert = $weatherService->checkAndCreateAlertsForChantier($chantier);

            if ($alert) {
                // Notify site manager
                if ($chantier->manager && $chantier->manager->user) {
                    $chantier->manager->user->notify(new WeatherAlertNotification($alert));
                }

                // Generate CIBTP Draft
                $declaration = $cibtpService->generateDraftFromAlert($alert);

                if ($declaration) {
                    // Notify RH
                    $rhUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'RH'))->get();
                    Notification::send($rhUsers, new CibtpDeclarationNeededNotification($declaration));
                }
            }
        }

        $this->info('Vérification météo terminée.');
    }
}
