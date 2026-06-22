<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tiers

Schedule::command('tiers:verify-vigilance')
    ->weeklyOn(1, '04:00');

// Articles
Schedule::command('articles:check-stocks')
    ->dailyAt('07:00');

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
Schedule::command('flottes:fleet-remind-assignments')
    ->dailyAt('18:00')
    ->timezone('Europe/Paris')
    ->onFailure(fn () => logger()->error("Échec de la commande de rappel d'affectation Flotte."));
