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
});
