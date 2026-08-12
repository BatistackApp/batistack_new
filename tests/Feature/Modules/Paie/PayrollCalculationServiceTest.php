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
});

it('filters contribution rates based on validity dates', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create(['pas_rate' => 0]);
    $profile = PayrollContributionProfile::factory()->create();
    
    // Create an old rate that expired in June 2026
    \App\Models\Paie\PayrollContributionRate::create([
        'payroll_contribution_profile_id' => $profile->id,
        'category' => 'Retraite',
        'label' => 'Ancien Taux',
        'employee_rate' => 5.0,
        'employer_rate' => 5.0,
        'base_formula' => \App\Enums\Paie\ContributionBaseFormula::GROSS_SALARY,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_to' => '2026-06-30',
    ]);
    
    // Create a new rate that starts in July 2026
    \App\Models\Paie\PayrollContributionRate::create([
        'payroll_contribution_profile_id' => $profile->id,
        'category' => 'Retraite',
        'label' => 'Nouveau Taux',
        'employee_rate' => 6.0,
        'employer_rate' => 6.0,
        'base_formula' => \App\Enums\Paie\ContributionBaseFormula::GROSS_SALARY,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_from' => '2026-07-01',
    ]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => now()->subMonths(2),
        'payroll_contribution_profile_id' => $profile->id,
        'weekly_hours' => 35,
        'hourly_rate' => 10.00, // 1516.70 gross
    ]);

    $service = new PayrollCalculationService();
    
    // Test for June 2026
    $payslipJune = $service->calculateForEmployee($employee, '2026-06', 151.67, $contract->hourly_rate);
    $juneLine = $payslipJune->lines()->where('category', 'Retraite')->first();
    expect($juneLine->label)->toBe('Ancien Taux');
    expect((float)$juneLine->employee_rate)->toBe(5.0);

    // Test for July 2026
    $payslipJuly = $service->calculateForEmployee($employee, '2026-07', 151.67, $contract->hourly_rate);
    $julyLine = $payslipJuly->lines()->where('category', 'Retraite')->first();
    expect($julyLine->label)->toBe('Nouveau Taux');
    expect((float)$julyLine->employee_rate)->toBe(6.0);
});

it('deducts absence days and adds indemnity if paid', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create(['pas_rate' => 0]);
    $profile = PayrollContributionProfile::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'payroll_contribution_profile_id' => $profile->id,
        'weekly_hours' => 35,
        'hourly_rate' => 10.00,
    ]);

    // Create a 3-day sickness absence (Wednesday to Friday)
    \App\Models\RH\Abscence::withoutEvents(function () use ($employee) {
        \App\Models\RH\Abscence::factory()->create([
            'employee_id' => $employee->id,
            'type' => \App\Enums\RH\AbsenceType::SICK_LEAVE,
            'start_date' => '2026-07-08',
            'end_date' => '2026-07-10',
            'is_paid' => false,
        ]);

        // Create a 2-day paid leave (Monday to Tuesday)
        \App\Models\RH\Abscence::factory()->create([
            'employee_id' => $employee->id,
            'type' => \App\Enums\RH\AbsenceType::PAID_LEAVE,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'is_paid' => true,
        ]);
    });

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, '2026-07', 151.67, $contract->hourly_rate);

    // Assert that the custom bonuses contain the absence deductions
    $bonuses = collect($payslip->custom_bonuses);
    
    // Sick leave deduction (3 days * 7h = 21h => -210)
    $sickLeaveDeduction = $bonuses->where('amount', -210.00)->first();
    expect($sickLeaveDeduction)->not->toBeNull();
    expect($sickLeaveDeduction['label'])->toContain('Arrêt Maladie');

    // Paid leave deduction (2 days * 7h = 14h => -140)
    $paidLeaveDeduction = $bonuses->where('amount', -140.00)->first();
    expect($paidLeaveDeduction)->not->toBeNull();
    expect($paidLeaveDeduction['label'])->toContain('Congés Payés');

    // Paid leave indemnity (+140)
    $paidLeaveIndemnity = $bonuses->where('amount', 140.00)->first();
    expect($paidLeaveIndemnity)->not->toBeNull();
    expect($paidLeaveIndemnity['label'])->toContain('Indemnité Congés Payés');
});

it('calculates carence and subrogation for ouvrier', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create();
    
    $profile = PayrollContributionProfile::factory()->create();
    \App\Models\Paie\PayrollContributionRate::create([
        'payroll_contribution_profile_id' => $profile->id,
        'category' => 'urssaf',
        'label' => 'URSSAF Maladie',
        'employee_rate' => 10,
        'employer_rate' => 20,
        'base_formula' => \App\Enums\Paie\ContributionBaseFormula::GROSS_SALARY,
        'is_deductible' => true,
    ]);

    $contract = Contract::withoutEvents(function () use ($employee, $profile) {
        return Contract::factory()->create([
            'employee_id' => $employee->id,
            'category' => \App\Enums\RH\EmployeeCategory::OUVRIER,
            'start_date' => now()->subYears(2),
            'weekly_hours' => 35,
            'hourly_rate' => 15.00,
            'payroll_contribution_profile_id' => $profile->id,
        ]);
    });

    $startDate = now()->startOfMonth()->next(\Carbon\Carbon::MONDAY);
    $endDate = $startDate->copy()->addDays(11); // 10 jours ouvrés

    $absence = \App\Models\RH\Abscence::factory()->create([
        'employee_id' => $employee->id,
        'type' => \App\Enums\RH\AbsenceType::SICK_LEAVE,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'requires_subrogation' => true,
    ]);

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, now()->format('Y-m'), 151.67, 15.00);

    expect($payslip)->not->toBeNull();
    expect($payslip->period)->toEqual(now()->format('Y-m'));
    expect((float)$payslip->custom_bonuses[0]['amount'])->toBeLessThan(0); // Déduction
    expect($payslip->custom_bonuses[1]['label'])->toBe('Maintien de salaire conventionnel');
});

it('calculates zero carence for work accident', function () {
    \App\Models\Core\Company::factory()->create();
    $employee = Employee::factory()->create();
    $profile = PayrollContributionProfile::factory()->create();
    \App\Models\Paie\PayrollContributionRate::create([
        'payroll_contribution_profile_id' => $profile->id,
        'category' => 'urssaf',
        'label' => 'URSSAF Maladie',
        'employee_rate' => 10,
        'employer_rate' => 20,
        'base_formula' => \App\Enums\Paie\ContributionBaseFormula::GROSS_SALARY,
        'is_deductible' => true,
    ]);

    $contract = Contract::withoutEvents(function () use ($employee, $profile) {
        return Contract::factory()->create([
            'employee_id' => $employee->id,
            'category' => \App\Enums\RH\EmployeeCategory::OUVRIER,
            'start_date' => now()->subMonths(6), // Pas d'ancienneté requise
            'weekly_hours' => 35,
            'hourly_rate' => 15.00,
            'payroll_contribution_profile_id' => $profile->id,
        ]);
    });

    $startDate = now()->startOfMonth()->next(\Carbon\Carbon::MONDAY);
    $endDate = $startDate->copy()->addDays(4); // 5 jours ouvrés

    $absence = \App\Models\RH\Abscence::factory()->create([
        'employee_id' => $employee->id,
        'type' => \App\Enums\RH\AbsenceType::WORK_ACCIDENT,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'requires_subrogation' => false,
    ]);

    $service = new PayrollCalculationService();
    $payslip = $service->calculateForEmployee($employee, now()->format('Y-m'), 151.67, 15.00);

    $deduction = $payslip->custom_bonuses[0]['amount'];
    $maintien = $payslip->custom_bonuses[1]['amount'];

    expect(abs($deduction))->toEqual((float)$maintien);
});
