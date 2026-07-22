<?php

use App\Enums\Paie\AdvancePaymentStatus;
use App\Models\Paie\AdvancePayment;
use App\Models\Paie\PayrollContributionProfile;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\Paie\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates the gross salary based on hours and rates', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create(['pas_rate' => 0]);
    $profile = PayrollContributionProfile::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'payroll_contribution_profile_id' => $profile->id,
        'weekly_hours' => 35,
        'hourly_rate' => 11.52,
    ]);

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, '2026-07', 151.67, $contract->hourly_rate);

    // 151.67 * 11.52 = 1747.2384 => round to 1747.24
    expect($payslip->gross_salary)->toEqual(1747.24);
    expect($payslip->period)->toBe('2026-07');
});

it('deducts advance payments from net paid', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create(['pas_rate' => 0]);
    $profile = PayrollContributionProfile::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'payroll_contribution_profile_id' => $profile->id,
        'weekly_hours' => 35,
        'hourly_rate' => 10.00, // 1516.70 gross
    ]);

    // Create an advance payment
    $advance = AdvancePayment::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 200.00,
        'status' => AdvancePaymentStatus::PAID,
    ]);

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, '2026-07', 151.67, $contract->hourly_rate);

    $advance->refresh();
    
    // Advance should be marked as deducted
    expect($advance->status)->toBe(AdvancePaymentStatus::DEDUCTED);
    expect($advance->payslip_id)->toBe($payslip->id);

    // net_payable (avant acompte) doit être > net_paid
    expect($payslip->net_paid)->toEqual(round($payslip->net_payable - 200, 2));
});

it('calculates pas amount correctly', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create(['pas_rate' => 5.00]); // 5% PAS
    $profile = PayrollContributionProfile::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'payroll_contribution_profile_id' => $profile->id,
        'weekly_hours' => 35,
        'hourly_rate' => 20.00, // 3033.40 gross
    ]);

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, '2026-07', 151.67, $contract->hourly_rate);

    $expectedPasAmount = round($payslip->taxable_net * 0.05, 2);

    expect($payslip->pas_rate)->toEqual(5.00);
    expect($payslip->pas_amount)->toEqual($expectedPasAmount);
    expect($payslip->net_paid)->toEqual(round($payslip->net_payable, 2));
});
