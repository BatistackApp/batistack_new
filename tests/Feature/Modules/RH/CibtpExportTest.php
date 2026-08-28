<?php

use App\Enums\RH\AbsenceType;
use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\RH\CibtpService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

it('calculates DNA totals correctly for the reference period', function () {
    $employee = Employee::factory()->create([
        'registration_number' => 'MAT-1234',
        'last_name' => 'Dupont',
        'first_name' => 'Jean',
    ]);

    // Contrat avec 20€/h sans déclencher l'observer (génération PDF)
    Contract::withoutEvents(function () use ($employee) {
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'hourly_rate' => 20,
            'start_date' => Carbon::now()->subYears(2),
        ]);
    });

    // Heure DANS la période de référence (Ex: Mai 2025 pour la DNA 2026)
    TimeEntry::create([
        'employee_id' => $employee->id,
        'date' => Carbon::create(2025, 5, 10),
        'hours' => 7,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    // Heure HORS période de référence (Ex: Février 2025 pour la DNA 2026)
    TimeEntry::create([
        'employee_id' => $employee->id,
        'date' => Carbon::create(2025, 2, 10),
        'hours' => 8,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    $service = app(CibtpService::class);
    // Période Avril 2025 - Mars 2026
    $csv = $service->generateDNA(2026);

    // Heures travaillées dans la période = 7
    // Salaire brut dans la période = 7 * 20 = 140

    expect($csv)->toContain('MAT-1234')
        ->and($csv)->toContain('Dupont')
        ->and($csv)->toContain('Jean')
        ->and($csv)->toContain('7') // Heures
        ->and($csv)->toContain('140'); // Salaire Brut
});

it('generates a valid CSV for DDC export and formats correctly', function () {
    $employee = Employee::factory()->create([
        'registration_number' => 'MAT-5678',
        'last_name' => 'Martin',
        'first_name' => 'Paul',
    ]);

    $absence = Abscence::create([
        'employee_id' => $employee->id,
        'type' => AbsenceType::PAID_LEAVE,
        'start_date' => Carbon::create(2026, 8, 10),
        'end_date' => Carbon::create(2026, 8, 20),
        'reason' => 'Vacances d\'été',
        'is_paid' => true,
    ]);

    $service = app(CibtpService::class);
    $csv = $service->generateDDC(new Collection([$absence]));

    $lastWorkedDay = Carbon::create(2026, 8, 9)->format('d/m/Y');

    expect($csv)->toContain('MAT-5678')
        ->and($csv)->toContain('Martin')
        ->and($csv)->toContain('10/08/2026')
        ->and($csv)->toContain('20/08/2026')
        ->and($csv)->toContain($lastWorkedDay)
        ->and($csv)->toContain('paid_leave');
});
