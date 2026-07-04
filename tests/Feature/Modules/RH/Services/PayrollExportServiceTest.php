<?php

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\RH\PayrollExportService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new PayrollExportService();
    $this->employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'registration_number' => 'MAT-001',
    ]);
});

test('it exports correct hours and absences', function () {
    $month = 1;
    $year = 2026;
    
    // Create TimeEntries inside the month
    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 10),
        'hours' => 7.5,
        'travel_hours' => 1,
        'is_grand_deplacement' => false,
        'status' => TimeEntryStatus::APPROVED,
    ]);
    
    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 11),
        'hours' => 8,
        'travel_hours' => 0,
        'is_grand_deplacement' => true,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    // Create a drafted time entry (should NOT be exported)
    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 12),
        'hours' => 5,
        'status' => TimeEntryStatus::DRAFT,
    ]);

    // Create Abscence (2 days)
    Abscence::factory()->create([
        'employee_id' => $this->employee->id,
        'start_date' => Carbon::create($year, $month, 20),
        'end_date' => Carbon::create($year, $month, 21),
    ]);

    $csv = $this->service->generateCsv($month, $year);

    // Assert CSV contains the employee row with correct calculated totals
    // 7.5 + 8 = 15.5 hours
    // 1 GD
    // 1 Travel hour
    // 2 Absence days
    
    expect($csv)->toContain('MAT-001')
        ->toContain('Doe')
        ->toContain('John')
        ->toContain('15.5') // Heures Normales
        ->toContain('1')    // GD
        ->toContain('2');   // Absences
});

test('it downloads the generated CSV with correct headers', function () {
    $month = 1;
    $year = 2026;
    
    $response = $this->service->downloadCsv($month, $year);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment; filename="export_paie_2026_1.csv"');
});
