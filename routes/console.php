<?php

use App\Jobs\Articles\CheckExpiringStocksJob;
use App\Jobs\Articles\CheckLowStockJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty')->everyMinute();
Schedule::command('model:prune')->daily();
Schedule::command('rh:sync-expired-roles')->dailyAt('01:00');

Schedule::command('inventory:generate-cycle-counts')->weeklyOn(1, '03:00');

// Commerce
Schedule::command('commerce:process-dunning')
    ->dailyAt('01:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec de la relance automatique des factures.'));

// Tiers

Schedule::command('tiers:process-email-campaigns')
    ->everyMinute();

Schedule::command('tiers:verify-vigilance')
    ->weeklyOn(1, '04:00');

// Articles
Schedule::command('articles:check-stocks')
    ->dailyAt('07:00');

// Schedule::job(new CheckLowStockJob)->dailyAt('08:00');
Schedule::job(new CheckExpiringStocksJob)->dailyAt('08:00');

// Météo & CIBTP
Schedule::command('weather:check-alerts')
    ->dailyAt('06:30')
    ->timezone('Europe/Paris');

Schedule::command('cibtp:notify-deadlines')
    ->weeklyOn(1, '08:00')
    ->timezone('Europe/Paris');

// RH
// Vérifier les habilitations expirant dans 30 jours (quotidien à 5h)
Schedule::command('rh:check-qualifications --days=30 --send')
    ->dailyAt('05:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec du scan des habilitations RH.'));

// Vérifier les visites médicales expirant dans 30 jours (quotidien à 6h)
Schedule::command('rh:check-medical-visits --days=30 --send')
    ->dailyAt('06:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec du scan des visites médicales.'));

// Vérifier les périodes d'essai se terminant dans 15 jours (quotidien à 7h)
Schedule::command('rh:check-trial-periods --days=15 --send')
    ->dailyAt('07:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec du scan des périodes d\'essai.'));

// Vérifier les équipements (EPI) expirés ou en maintenance (quotidien à 8h)
Schedule::command('rh:check-equipement --send')
    ->dailyAt('08:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec du scan des équipements RH.'));

Schedule::command('rh:detect-time-anomalies')
    ->dailyAt('22:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec de la détection d\'anomalies de pointage.'));

// Synchroniser les heures travaillées (quotidien à 23h - fin de journée)
Schedule::command('rh:sync-employee-hours')
    ->dailyAt('23:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Échec de la synchronisation des heures.'));

// Nettoyer les données obsolètes (1er du mois à 3h du matin)
Schedule::command('rh:cleanup --months=12 --force')
    ->monthlyOn(1, '03:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec du nettoyage des données RH.'));

// CHANTIER
Schedule::command('chantier:check-compliance')
    ->dailyAt('07:00')
    ->onFailure(fn () => logger()->error('Échec du scan de conformité sécurité.'));

Schedule::command('chantiers:remind-starts')
    ->dailyAt('08:30');

Schedule::command('chantiers:check-late')
    ->dailyAt('09:00');

Schedule::command('chantiers:missing-alert-logs')
    ->weekdays()
    ->at('19:00')
    ->timezone('Europe/Paris');

Schedule::command('chantiers:sync-metrics --all')
    ->dailyAt('02:30')
    ->withoutOverlapping();

// Flottes
Schedule::command('flottes:fleet-supervisor')->dailyAt('06:00');
Schedule::command('flottes:fleet-supervisor --alert')->weeklyOn(1, '19:00');
Schedule::command('flottes:remind-assignments')
    ->dailyAt('18:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error("Échec de la commande de rappel d'affectation Flotte."));

// Banque
Schedule::command('banque:sync-bridge')
    ->dailyAt('04:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec de la synchronisation des comptes bancaires Bridge.'));

Schedule::command('app:check-bridge-tokens')
    ->dailyAt('09:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error('Échec de la vérification des tokens Bridge API.'));

// Interventions
Schedule::command('interventions:generate-maintenance')
    ->dailyAt('06:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Échec de la génération des interventions de maintenance.'));

Schedule::command('interventions:remind-maintenance')
    ->dailyAt('07:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Échec de l\'envoi des rappels d\'entretien.'));
