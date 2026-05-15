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
Schedule::command('rh:check-qualifications')
    ->dailyAt('05:00');

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
