<?php

use App\Jobs\Paie\GenerateMassPayslipsJob;
use App\Models\Paie\PayrollContributionProfile;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates mass payslips for employees', function () {
    \App\Models\Core\Company::factory()->create();
    $profile = PayrollContributionProfile::factory()->create();
    $employee1 = Employee::factory()->create();
    $employee2 = Employee::factory()->create();

    Contract::factory()->create([
        'employee_id' => $employee1->id,
        'payroll_contribution_profile_id' => $profile->id,
    ]);
    Contract::factory()->create([
        'employee_id' => $employee2->id,
        'payroll_contribution_profile_id' => $profile->id,
    ]);

    $job = new GenerateMassPayslipsJob('2026-08');
    app()->call([$job, 'handle']);

    // Verify two payslips were generated for the period
    $payslipsCount = \App\Models\Paie\Payslip::where('period', '2026-08')->count();
    expect($payslipsCount)->toBe(2);
});
