<?php

use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Services\Core\GoogleMapsService;
use App\Services\RH\PayrollVariableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Company::factory()->create();
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

describe('Calculs de Paie et Indemnités', function () {

    test('il calcule correctement les heures supplémentaires hebdomadaires', function () {
        $employee = Employee::factory()->create();

        $monday = Carbon::parse('2025-01-06'); // Un lundi

        // Création de 45h de travail approuvées
        TimeEntry::factory()->count(5)->create([
            'employee_id' => $employee->id,
            'date' => $monday,
            'hours' => 9, // 5 * 9 = 45h
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $service = app(PayrollVariableService::class);
        $res = $service->calculateWeeklyOvertime($employee, $monday);

        // Attendu : 35h normales, 8h à 25% (35-43), 2h à 50% (43-45)
        expect($res['normal_hours'])->toEqual(35)
            ->and($res['overtime_25'])->toEqual(8)
            ->and($res['overtime_50'])->toEqual(2);
    });

    test('il applique le forfait de 96€ pour le Grand Déplacement (>50km)', function () {
        // Mock du GoogleMapsService pour simuler une distance de 60km
        $this->mock(GoogleMapsService::class, function ($mock) {
            $mock->shouldReceive('getDistanceMatrix')
                ->andReturn([
                    'distance_text' => '60 km',
                    'distance_value' => 60000, // 60 km
                    'duration_text' => '1 hour 0 mins',
                    'duration_value' => 3600, // 1 hour
                ]);
        });

        $service = app(PayrollVariableService::class);
        $res = $service->calculateTravelAllowance('Adresse Chantier Lointain');

        expect($res['is_gd'])->toBeTrue()
            ->and($res['allowance'])->toEqual(96.00);
    });
});
