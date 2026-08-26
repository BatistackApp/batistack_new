<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Services\Chantiers\ChantierLogService;

test('il génère une synthèse quotidienne exacte', function () {
    $service = new ChantierLogService;
    $chantier = Chantier::factory()->create();
    $user = User::factory()->create();
    $today = now();

    // Création d'un log avec incident
    ChantierLog::factory()->create([
        'chantier_id' => $chantier->id,
        'user_id' => $user->id,
        'date' => $today,
        'incident_reported' => true,
        'weather_condition' => 'Pluie',
        'content' => 'Panne machine',
    ]);

    // Création de 2 pointages pour ce jour
    TimeEntry::factory()->count(2)->create([
        'chantier_id' => $chantier->id,
        'date' => $today,
    ]);

    $summary = $service->getDailySummary($chantier, $today);

    expect($summary['weather'])->toBe('Pluie')
        ->and($summary['incidents'])->toBe(1)
        ->and($summary['present_employees'])->toBe(2)
        ->and($summary['notes'])->toContain('Panne machine');
});
