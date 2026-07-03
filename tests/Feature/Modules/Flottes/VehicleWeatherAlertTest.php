<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use App\Models\Flottes\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('retrieves overlapping weather alerts for a vehicle assignment', function () {
    $chantier = Chantier::factory()->create();

    $assignment = VehicleAssignment::factory()->create([
        'chantier_id' => $chantier->id,
        'started_at' => now()->subDays(5),
        'ended_at' => now()->subDays(1),
    ]);

    // This alert should overlap
    $overlappingAlert1 = WeatherAlert::create([
        'chantier_id' => $chantier->id,
        'alert_date' => now()->subDays(3),
        'started_at' => now()->subDays(3),
        'ended_at' => now()->subDays(3)->addHours(2),
        'type' => 'tempete',
        'severity' => 'haute',
        'description' => 'Test alert',
    ]);

    // This alert is before the assignment
    $pastAlert = WeatherAlert::create([
        'chantier_id' => $chantier->id,
        'alert_date' => now()->subDays(10),
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(10)->addHours(2),
        'type' => 'tempete',
        'severity' => 'haute',
        'description' => 'Test alert',
    ]);

    // This alert is after the assignment
    $futureAlert = WeatherAlert::create([
        'chantier_id' => $chantier->id,
        'alert_date' => now(),
        'started_at' => now(),
        'ended_at' => now()->addHours(2),
        'type' => 'tempete',
        'severity' => 'haute',
        'description' => 'Test alert',
    ]);

    $alerts = $assignment->getOverlappingWeatherAlerts();

    expect($alerts)->toHaveCount(1)
        ->and($alerts->first()->id)->toBe($overlappingAlert1->id);
});
