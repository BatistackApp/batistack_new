<?php

use App\Models\RH\Employee;
use App\Models\RH\WageGarnishment;

it('calculates automatic deduction correctly', function () {
    $employee = Employee::factory()->create();

    $garnishment = WageGarnishment::create([
        'employee_id' => $employee->id,
        'reference' => 'TEST-SATD-1',
        'total_amount_due' => 1000,
        'amount_collected' => 0,
        'start_date' => now(),
        'is_active' => true,
    ]);

    // Net salary below RSA (635)
    expect($garnishment->calculateDeduction(600))->toEqual(0.0);

    // Net salary 800
    // Tranche 1 (434 à 800): 800 - 434 = 366. 366 / 5 = 73.2
    expect($garnishment->calculateDeduction(800))->toEqual(round((800 - 434) / 5, 2));

    // Test with monthly_deduction override
    $garnishment->update(['monthly_deduction' => 50.0]);
    expect($garnishment->calculateDeduction(800))->toEqual(50.0);

    // Test limits (cannot deduct more than what is owed)
    $garnishment->update(['monthly_deduction' => null, 'total_amount_due' => 1000, 'amount_collected' => 950]);
    expect($garnishment->calculateDeduction(1500))->toEqual(50.0);
});
