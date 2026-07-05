<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates a uuid when creating a chantier', function () {
    $chantier = Chantier::factory()->create();
    
    expect($chantier->uuid)->not->toBeNull()
        ->and(Str::isUuid($chantier->uuid))->toBeTrue();
});

it('casts attributes correctly', function () {
    $chantier = Chantier::factory()->create([
        'status' => ChantierStatus::STUDY,
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'budget_hours' => 150.5,
    ]);
    
    expect($chantier->status)->toBeInstanceOf(ChantierStatus::class)
        ->and($chantier->status)->toBe(ChantierStatus::STUDY)
        ->and($chantier->latitude)->toBe(48.8566)
        ->and($chantier->longitude)->toBe(2.3522)
        ->and((float) $chantier->budget_hours)->toBe(150.50);
});

it('returns the full address attribute', function () {
    $chantier = Chantier::factory()->create([
        'address' => '10 Rue de la Paix',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
    
    expect($chantier->full_address)->toBe('10 Rue de la Paix, 75000 Paris');
});

it('returns the location attribute', function () {
    $chantier = Chantier::factory()->create([
        'latitude' => 48.8566,
        'longitude' => 2.3522,
    ]);
    
    expect($chantier->location)->toBeArray()
        ->and($chantier->location)->toHaveCount(2)
        ->and($chantier->location[0])->toBe(48.8566)
        ->and($chantier->location[1])->toBe(2.3522);
});

it('calculates the real hours correctly', function () {
    $chantier = Chantier::factory()->create();
    
    // Approved time entry
    TimeEntry::factory()->create([
        'chantier_id' => $chantier->id,
        'hours' => 8,
        'status' => TimeEntryStatus::APPROVED,
    ]);
    
    // Approved time entry
    TimeEntry::factory()->create([
        'chantier_id' => $chantier->id,
        'hours' => 4.5,
        'status' => TimeEntryStatus::APPROVED,
    ]);
    
    // Draft time entry (should not be counted)
    TimeEntry::factory()->create([
        'chantier_id' => $chantier->id,
        'hours' => 5,
        'status' => TimeEntryStatus::DRAFT,
    ]);
    
    expect($chantier->real_hours)->toBe(12.5);
});
