<?php

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\RH\LeaveBalanceService;
use Carbon\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

describe('Gestion des Congés', function () {

    test('le décompte des congés exclut les week-ends', function () {
        $employee = Employee::factory()->create();

        // Absence du vendredi au lundi (4 jours calendaires, mais 2 jours ouvrés : Ven et Lun)
        Abscence::create([
            'employee_id' => $employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::parse('2025-01-10'), // Vendredi
            'end_date' => Carbon::parse('2025-01-13'),   // Lundi
            'is_paid' => true,
        ]);

        $service = new LeaveBalanceService;
        $consumed = $service->getConsumedDays($employee, AbsenceType::PAID_LEAVE);

        expect($consumed)->toEqual(2.0);
    });

    test('getAcquiredRights retourne 0 si pas de contrat', function () {
        $employee = Employee::factory()->create();
        $service = new LeaveBalanceService;
        
        expect($service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE))->toBe(0.0);
    });

    test('getAcquiredRights calcule depuis le début de l\'année si le contrat est ancien', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 0, 0, 0)); // June 1st, 2026

        $employee = Employee::factory()->create();
        \App\Models\RH\Contract::withoutEvents(function () use ($employee) {
            \App\Models\RH\Contract::factory()->create([
                'employee_id' => $employee->id,
                'start_date' => Carbon::create(2024, 1, 1, 0, 0, 0), // 2024
            ]);
        });
        $employee->load('currentContract'); // Ensure relation is loaded from DB

        $service = new LeaveBalanceService;
        
        // From Jan 1st 2026 to June 1st 2026 = 5 months
        // 5 * 2.5 = 12.5
        expect($service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE))->toEqual(12.5)
            ->and($service->getAcquiredRights($employee, AbsenceType::RTT))->toEqualWithDelta(4.15, 0.01) // 5 * 0.83
            ->and($service->getAcquiredRights($employee, AbsenceType::UNPAID_LEAVE))->toEqual(0.0);
            
        Carbon::setTestNow();
    });

    test('getAcquiredRights calcule depuis la date de début si le contrat a commencé cette année', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 0, 0, 0)); // June 1st, 2026

        $employee = Employee::factory()->create();
        
        \App\Models\RH\Contract::withoutEvents(function () use ($employee) {
            \App\Models\RH\Contract::factory()->create([
                'employee_id' => $employee->id,
                'start_date' => Carbon::create(2026, 4, 1, 0, 0, 0), // April 1st, 2026
            ]);
        });
        $employee->load('currentContract');

        $service = new LeaveBalanceService;
        
        // From April 1st to June 1st = 2 months
        // 2 * 2.5 = 5.0
        expect($service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE))->toEqual(5.0);
        
        Carbon::setTestNow();
    });

    test('getBalance soustrait les jours consommés des droits acquis', function () {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 0, 0, 0)); // June 1st, 2026

        $employee = Employee::factory()->create();
        
        \App\Models\RH\Contract::withoutEvents(function () use ($employee) {
            \App\Models\RH\Contract::factory()->create([
                'employee_id' => $employee->id,
                'start_date' => Carbon::create(2024, 1, 1, 0, 0, 0),
            ]);
        });
        $employee->load('currentContract');

        // Consume 3 days (Mercredi to Vendredi)
        Abscence::create([
            'employee_id' => $employee->id,
            'type' => AbsenceType::PAID_LEAVE,
            'start_date' => Carbon::create(2026, 5, 13, 0, 0, 0), // Wednesday
            'end_date' => Carbon::create(2026, 5, 15, 0, 0, 0),   // Friday
            'is_paid' => true,
        ]);

        $service = new LeaveBalanceService;
        
        // 12.5 acquired - 3 consumed = 9.5
        $balance = $service->getBalance($employee, AbsenceType::PAID_LEAVE);
        
        expect($balance)->toEqual(9.5);
        
        Carbon::setTestNow();
    });
});
